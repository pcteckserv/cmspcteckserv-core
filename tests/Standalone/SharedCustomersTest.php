<?php

namespace Pcteckserv\CmsCore\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase;
use Pcteckserv\CmsCore\CmsCoreServiceProvider;
use Pcteckserv\CmsCore\Contracts\CmsAccessUser;
use Pcteckserv\CmsCore\Customers\Address;
use Pcteckserv\CmsCore\Customers\Customer;
use Pcteckserv\CmsCore\Customers\CustomerDirectory;
use Pcteckserv\CmsCore\Customers\Group;
use Pcteckserv\CmsCore\Models\Concerns\HasCmsAccess;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Security\AccountFactor;
use Pcteckserv\CmsCore\Security\AuthenticatorQr;
use Pcteckserv\CmsCore\Security\TwoFactor;

class CustomerTestUser extends User implements CmsAccessUser
{
    use HasCmsAccess;

    protected $table = 'users';

    protected $guarded = ['id'];
}

class SharedCustomersTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CmsCoreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $app['config']->set('auth.providers.users.model', CustomerTestUser::class);
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('mail.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->artisan('migrate', ['--force' => true])->run();
    }

    protected function customer(): Customer
    {
        return Customer::create(['name' => 'Maria', 'email' => 'maria@example.test', 'password' => 'password-test-123']);
    }

    protected function manager(): CustomerTestUser
    {
        $user = CustomerTestUser::create(['name' => 'Gestor', 'email' => 'gestor@example.test', 'password' => bcrypt('password-test-123')]);
        $user->cmsRoles()->attach(Role::where('key', 'ecommerce.manager')->firstOrFail());

        return $user;
    }

    public function test_customers_and_roles_work_without_any_shop_or_agenda_plugin(): void
    {
        $c = $this->customer();
        $this->assertTrue($c->hasCmsRole('core.client'));
        $this->assertFalse($c->hasCmsPermission('core.users.view'));
        $this->actingAs($c, 'shop_customer')->get('/cliente')->assertOk()->assertSee('Maria')->assertDontSee('Marcações e serviços');
        $this->get('/admin/clientes')->assertRedirect('/login');
        $this->actingAs($this->manager(), 'web')->get('/admin/clientes')->assertOk()->assertSee('Maria');
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/clientes/'.$c->id)->assertOk()->assertSee('Moradas guardadas');
    }

    public function test_contact_resolution_does_not_login_or_overwrite_existing_customer(): void
    {
        $c = $this->customer();
        $hash = $c->password;
        $same = app(CustomerDirectory::class)->contact('Nome enviado por terceiro', ' MARIA@example.test ', '900000000');
        $this->assertSame($c->id, $same->id);
        $this->assertSame('Maria', $same->name);
        $this->assertSame($hash, $same->password);
        $this->assertGuest('shop_customer');
        $new = app(CustomerDirectory::class)->contact('João', 'joao@example.test');
        $this->assertTrue($new->hasCmsRole('core.client'));
    }

    public function test_admin_manages_groups_addresses_and_preserves_customer_with_history(): void
    {
        $c = $this->customer();
        $this->actingAs($this->manager());
        $this->post('/admin/clientes/grupos', ['name' => 'Profissionais', 'description' => 'Clientes profissionais'])->assertSessionHasNoErrors();
        $group = Group::firstOrFail();
        $this->put('/admin/clientes/'.$c->id, ['name' => 'Maria', 'email' => $c->email, 'active' => 1, 'groups' => [$group->id]])->assertSessionHasNoErrors();
        $this->assertSame([$group->id], $c->groups()->pluck('cms_customer_groups.id')->all());
        $this->delete('/admin/clientes/grupos/'.$group->id)->assertSessionHasErrors('group');
        $data = ['label' => 'Casa', 'name' => 'Maria', 'address' => 'Rua 1', 'city' => 'Lisboa', 'postal_code' => '1000-001', 'country' => 'PT'];
        $this->post('/admin/clientes/'.$c->id.'/moradas', $data)->assertSessionHasNoErrors();
        $a = Address::firstOrFail();
        $this->put('/admin/clientes/'.$c->id.'/moradas/'.$a->id, array_merge($data, ['city' => 'Porto']))->assertSessionHasNoErrors();
        $this->assertSame('Porto', $a->fresh()->details['city']);
        $other = Customer::create(['name' => 'Outro', 'email' => 'other@example.test', 'password' => 'password-test-123']);
        $this->put('/admin/clientes/'.$other->id.'/moradas/'.$a->id, $data)->assertNotFound();
        $this->delete('/admin/clientes/'.$c->id)->assertSessionHasNoErrors();
        $this->assertFalse($c->fresh()->active);
        $this->assertDatabaseCount('cms_shop_addresses', 1);
        $this->delete('/admin/clientes/'.$other->id)->assertSessionHasNoErrors();
        $this->assertNull($other->fresh());
    }

    public function test_totp_matches_rfc_vector_rejects_replay_and_encrypts_secret(): void
    {
        $two = app(TwoFactor::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertSame('287082', $two->code($secret, 1));
        $this->travelTo(Carbon::createFromTimestampUTC(59));
        $factor = AccountFactor::create(['guard' => 'shop_customer', 'account_id' => $this->customer()->id, 'secret' => $secret, 'confirmed_at' => now(), 'recovery_codes' => [hash('sha256', 'recovery-example')]]);
        $this->assertStringNotContainsString($secret, DB::table('cms_account_factors')->value('secret'));
        $this->assertTrue($two->consume($factor, '287082'));
        $this->assertFalse($two->consume($factor, '287082'));
        $this->assertTrue($two->consume($factor, 'recovery-example'));
        $this->assertFalse($two->consume($factor, 'recovery-example'));
        $this->assertFalse($two->consume($factor, '000000'));
        $this->travelBack();
    }

    public function test_customer_login_requires_second_factor_before_authentication(): void
    {
        $c = $this->customer();
        $two = app(TwoFactor::class);
        $factor = AccountFactor::create(['guard' => 'shop_customer', 'account_id' => $c->id, 'secret' => $two->secret(), 'confirmed_at' => now()]);
        $this->post('/cliente/entrar', ['email' => $c->email, 'password' => 'password-test-123'])->assertRedirect(route('security.challenge'));
        $this->assertGuest('shop_customer');
        $this->post('/seguranca/verificar', ['code' => 'invalid'])->assertSessionHasErrors('code');
        $this->assertGuest('shop_customer');
        $this->post('/seguranca/verificar', ['code' => $two->code($factor->secret, intdiv(now()->timestamp, 30))])->assertRedirect(route('customers.account'));
        $this->assertAuthenticatedAs($c, 'shop_customer');
        $this->get('/cliente')->assertOk();
        $this->get('/admin/clientes')->assertRedirect('/login');
    }

    public function test_setup_requires_password_confirmation_and_only_enables_after_valid_code(): void
    {
        $c = $this->customer();
        $this->actingAs($c, 'shop_customer');
        $this->post('/seguranca/shop_customer/preparar', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/seguranca/shop_customer/preparar', ['password' => 'password-test-123'])->assertSessionHasNoErrors();
        $factor = AccountFactor::firstOrFail();
        $this->assertNull($factor->confirmed_at);
        $this->get('/seguranca/shop_customer')->assertOk()->assertSee($factor->secret);
        $this->post('/seguranca/shop_customer/confirmar', ['password' => 'password-test-123', 'code' => app(TwoFactor::class)->code($factor->secret, intdiv(now()->timestamp, 30))])->assertSessionHas('recovery_codes')->assertSessionHasNoErrors();
        $this->assertNotNull($factor->fresh()->confirmed_at);
        $this->get('/seguranca/shop_customer')->assertOk()->assertDontSee($factor->secret);
    }

    public function test_client_role_in_web_guard_never_gives_admin_access(): void
    {
        $u = $this->manager();
        $u->cmsRoles()->sync([Role::where('key', 'core.client')->firstOrFail()->id]);
        $this->actingAs($u, 'web')->get('/admin')->assertForbidden();
        $this->get('/admin/clientes')->assertForbidden();
    }

    public function test_admin_two_factor_and_restored_sessions_require_a_valid_challenge(): void
    {
        $u = $this->manager();
        $two = app(TwoFactor::class);
        $factor = AccountFactor::create(['guard' => 'web', 'account_id' => $u->id, 'secret' => $two->secret(), 'confirmed_at' => now()]);
        $this->actingAs($u, 'web')->get('/admin/clientes')->assertRedirect(route('security.challenge'));
        $this->assertGuest('web');
        $this->post('/seguranca/verificar', ['code' => $two->code($factor->secret, intdiv(now()->timestamp, 30))])->assertRedirect(route('admin.customers.index'));
        $this->get('/admin/clientes')->assertOk();
        $this->get('/seguranca/web')->assertOk()->assertSee('cms-access-submenu')->assertDontSee($factor->secret);
    }

    public function test_expired_or_password_revoked_challenge_never_authenticates(): void
    {
        $c = $this->customer();
        $two = app(TwoFactor::class);
        $factor = AccountFactor::create(['guard' => 'shop_customer', 'account_id' => $c->id, 'secret' => $two->secret(), 'confirmed_at' => now()]);
        $this->post('/cliente/entrar', ['email' => $c->email, 'password' => 'password-test-123'])->assertRedirect(route('security.challenge'));
        $c->update(['password' => 'new-password-test-123']);
        $this->post('/seguranca/verificar', ['code' => $two->code($factor->secret, intdiv(now()->timestamp, 30))])->assertForbidden();
        $this->assertGuest('shop_customer');
        $this->post('/cliente/entrar', ['email' => $c->email, 'password' => 'new-password-test-123'])->assertRedirect(route('security.challenge'));
        $this->travel(6)->minutes();
        $this->post('/seguranca/verificar', ['code' => $two->code($factor->secret, intdiv(now()->timestamp, 30))])->assertForbidden();
        $this->assertGuest('shop_customer');
        $this->travelBack();
    }

    public function test_customer_address_routes_are_private_and_independent_of_shop(): void
    {
        $c = $this->customer();
        $this->get('/cliente/moradas')->assertRedirect(route('customers.login'));
        $this->actingAs($c, 'shop_customer')->get('/cliente/moradas')->assertOk();
        $data = ['label' => 'Casa', 'name' => 'Maria', 'address' => 'Rua 1', 'city' => 'Lisboa', 'postal_code' => '1000-001', 'country' => 'PT'];
        $this->post('/cliente/moradas', $data)->assertSessionHasNoErrors();
        $a = Address::firstOrFail();
        $this->put('/cliente/moradas/'.$a->id, array_merge($data, ['country' => 'ZZ']))->assertSessionHasErrors('country');
        $other = Customer::create(['name' => 'Outro', 'email' => 'other@example.test', 'password' => 'password-test-123']);
        $this->actingAs($other, 'shop_customer')->delete('/cliente/moradas/'.$a->id)->assertNotFound();
    }

    public function test_authenticator_qr_is_local_private_and_disappears_after_confirmation(): void
    {
        $customer = $this->customer();
        $two = app(TwoFactor::class);
        $factor = AccountFactor::create(['guard' => 'shop_customer', 'account_id' => $customer->id, 'secret' => $two->secret()]);
        config(['app.name' => 'Empresa & Filhos: Agenda']);
        $qr = app(AuthenticatorQr::class);
        $uri = $qr->uri($factor, $customer->email);
        parse_str(parse_url($uri, PHP_URL_QUERY), $params);
        $this->assertSame($factor->secret, $params['secret']);
        $this->assertSame('Empresa & Filhos  Agenda', $params['issuer']);
        $this->assertSame('6', $params['digits']);
        $this->assertSame('30', $params['period']);
        $this->actingAs($customer, 'shop_customer')->get('/seguranca/shop_customer')->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertSee('data:image/svg+xml;base64,', false)->assertSee('Não consegue ler o QR code?');
        $factor->update(['confirmed_at' => now()]);
        $this->get('/seguranca/shop_customer')->assertOk()->assertDontSee('data-authenticator-qr')->assertDontSee($factor->secret);
    }

    public function test_staff_can_update_only_their_own_profile_with_current_password(): void
    {
        $staff = $this->manager();
        $other = CustomerTestUser::create(['name' => 'Outro', 'email' => 'other@example.test', 'password' => bcrypt('password-test-123')]);
        $this->actingAs($staff, 'web')->get('/admin/perfil')->assertOk()->assertSee('O meu perfil')->assertSee('cms-profile-menu');
        $data = ['name' => 'Nome atualizado', 'email' => 'UPDATE@example.test', 'current_password' => 'wrong', 'role_id' => Role::where('key', 'core.super_admin')->value('id'), 'id' => $other->id];
        $this->put('/admin/perfil', $data)->assertSessionHasErrors('current_password');
        $this->assertSame('Gestor', $staff->fresh()->name);
        $data['current_password'] = 'password-test-123';
        $data['password'] = 'new-password-test-123';
        $data['password_confirmation'] = $data['password'];
        $this->put('/admin/perfil', $data)->assertSessionHasNoErrors();
        $this->assertSame('update@example.test', $staff->fresh()->email);
        $this->assertTrue(Hash::check($data['password'], $staff->fresh()->password));
        $this->assertFalse($staff->fresh()->isCmsSuperAdmin());
        $this->assertSame('Outro', $other->fresh()->name);
        $this->get('/admin/perfil')->assertOk();
    }

    public function test_profile_routes_keep_staff_and_customer_sessions_separate(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'shop_customer')->get('/admin/perfil')->assertRedirect('/login');
        $this->get('/cliente/perfil')->assertOk()->assertSee('maria@example.test');
        $this->get('/cliente')->assertOk()->assertSee('customer-account-menu')->assertSee(route('customers.profile'));
        $this->assertGuest('web');
    }

    public function test_staff_profile_rejects_duplicate_email_and_revoked_sessions(): void
    {
        $staff = $this->manager();
        CustomerTestUser::create(['name' => 'Outro', 'email' => 'other@example.test', 'password' => bcrypt('password-test-123')]);
        $this->actingAs($staff, 'web')->put('/admin/perfil', ['name' => 'Gestor', 'email' => 'other@example.test', 'current_password' => 'password-test-123'])->assertSessionHasErrors('email');
        $previous = ['id' => $staff->id, 'hash' => $staff->password];
        $staff->forceFill(['password' => bcrypt('new-password-test-123')])->save();
        $this->withSession(['cms_account.identity.web' => $previous])->get('/admin/perfil')->assertRedirect('/login');
        $this->assertGuest('web');
    }
}
