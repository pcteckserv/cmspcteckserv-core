<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Pcteckserv\CmsCore\Models\ActivityLog;
use Pcteckserv\CmsCore\Models\TwoFactorAuthentication;
use Pcteckserv\CmsCore\Services\Auth\PendingTwoFactorLogin;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;
use Pcteckserv\CmsCore\Services\DeleteUser;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use RuntimeException;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_cartao_disponivel_sem_permissoes_de_gestao(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get(route('admin.profile.edit'))->assertOk()
            ->assertSee('Autenticação de dois fatores')->assertSee('Configurar autenticação');
    }

    public function test_visitantes_nao_podem_configurar_confirmar_desativar_ou_regenerar(): void
    {
        foreach (['begin', 'confirm', 'regenerate'] as $action) {
            $this->post(route('admin.profile.two-factor.'.$action))->assertRedirect(route('login'));
        }
        $this->delete(route('admin.profile.two-factor.disable'))->assertRedirect(route('login'));
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.verify'), ['two_factor_code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_configuracao_exige_password_atual_e_so_ativa_apos_codigo_correto(): void
    {
        $user = $this->user();
        $this->actingAs($user)->from(route('admin.profile.edit'))
            ->post(route('admin.profile.two-factor.begin'), ['current_password' => 'incorreta'])
            ->assertSessionHasErrors('current_password');
        $this->assertDatabaseCount('cms_two_factor_authentications', 0);

        $factor = $this->begin($user);
        $this->assertNull($factor->confirmed_at);
        $this->assertNotSame($factor->secret, $factor->getRawOriginal('secret'));
        $this->assertArrayNotHasKey('secret', $factor->toArray());
        $this->get(route('admin.profile.edit'))->assertOk()->assertSee($factor->secret)
            ->assertSee('data:image/svg+xml;base64,', false)->assertHeader('Cache-Control', 'no-store, private');

        $this->post(route('admin.profile.two-factor.confirm'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => 'invalido',
        ])->assertSessionHasErrors('two_factor_code');
        $this->assertNull($factor->fresh()->confirmed_at);

        $response = $this->post(route('admin.profile.two-factor.confirm'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => $this->code($factor),
        ])->assertOk()->assertViewIs('cms-core::admin.profile.recovery-codes');
        $codes = $response->viewData('codes');
        $this->assertCount(8, $codes);
        $this->assertNotNull($factor->fresh()->confirmed_at);
        $this->assertSame(hash('sha256', $codes[0]), $factor->fresh()->recovery_codes[0]);
        $this->get(route('admin.profile.edit'))->assertOk()->assertDontSee($factor->secret)->assertDontSee($codes[0]);
        $this->assertStringNotContainsString($factor->secret, ActivityLog::query()->get()->toJson());
        $this->assertStringNotContainsString($codes[0], ActivityLog::query()->get()->toJson());
        $this->assertNull(session('_old_input.two_factor_code'));
    }

    public function test_configuracao_expira_e_nao_pode_ser_confirmada_noutra_sessao(): void
    {
        $user = $this->user();
        $factor = $this->begin($user);
        $this->withSession([TwoFactorManager::SETUP => 'outra-sessao'])
            ->get(route('admin.profile.edit'))->assertDontSee($factor->secret);
        $this->post(route('admin.profile.two-factor.confirm'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => $this->code($factor),
        ])->assertSessionHasErrors('two_factor_code');
        $this->withSession([TwoFactorManager::SETUP => $factor->version]);
        $this->travel(11)->minutes();
        $this->post(route('admin.profile.two-factor.confirm'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => $this->code($factor),
        ])->assertSessionHasErrors('two_factor_code');
        $this->assertNull($factor->fresh()->confirmed_at);
    }

    public function test_login_com_2fa_nao_autentica_nem_regista_sucesso_antes_do_segundo_fator(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        $this->post(route('login.store'), [
            'email' => $user->email, 'password' => 'Atual-segura-123', 'remember' => 1,
        ])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertDatabaseMissing('cms_activity_logs', ['action' => 'auth.login']);
        $this->assertNull($user->fresh()->cmsState);
        $this->get(route('admin.profile.edit'))->assertRedirect(route('login'));
        $this->get(route('two-factor.challenge'))->assertOk()->assertDontSee($factor->secret);
        $this->post(route('two-factor.verify'), ['two_factor_code' => 'errado'])->assertSessionHasErrors('two_factor_code');
        $this->assertGuest();
        $this->post(route('two-factor.verify'), ['two_factor_code' => $this->code($factor)])
            ->assertRedirect(route('admin.profile.edit'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('cms_activity_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
        $this->assertNotNull($user->fresh()->cmsState->last_login_at);
        $this->assertNotNull($user->fresh()->getRememberToken());
        $this->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_codigo_totp_nao_pode_ser_reutilizado(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        $manager = app(TwoFactorManager::class);
        $code = $this->code($factor);
        $this->assertNotNull($manager->verify($user, $code));
        $this->assertNull($manager->verify($user, $code));
    }

    public function test_codigo_de_recuperacao_e_de_utilizacao_unica_no_login(): void
    {
        $user = $this->user();
        $factor = $this->begin($user);
        $codes = app(TwoFactorManager::class)->confirm($user, $factor->version, $this->code($factor));
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        $this->post(route('two-factor.verify'), ['two_factor_code' => $codes[0]])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertCount(7, $factor->fresh()->recovery_codes);
        $this->assertNull(app(TwoFactorManager::class)->verify($user, $codes[0]));
    }

    public function test_desafio_expirado_ou_password_alterada_nao_autentica(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        $this->travel(6)->minutes();
        $this->post(route('two-factor.verify'), ['two_factor_code' => $this->code($factor)])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->travelBack();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        $user->update(['password' => Hash::make('Mudada-segura-456')]);
        $this->post(route('two-factor.verify'), ['two_factor_code' => $this->code($factor)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_conta_desativada_nao_pode_concluir_desafio(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        $user->cmsState()->create(['state' => 'inactive']);
        $this->post(route('two-factor.verify'), ['two_factor_code' => $this->code($factor)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sessao_anterior_e_obrigada_a_validar_segundo_fator(): void
    {
        $user = $this->user();
        $this->enabled($user);
        $this->actingAs($user)->get(route('admin.profile.edit'))->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertSame($user->id, session(PendingTwoFactorLogin::KEY.'.id'));
    }

    public function test_limita_tentativas_e_nao_guarda_codigos_no_old_input(): void
    {
        $user = $this->user();
        $this->enabled($user);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('two-factor.challenge'))->post(route('two-factor.verify'), ['two_factor_code' => ['manipulado']])
                ->assertSessionHasErrors('two_factor_code');
        }
        $this->assertNull(session('_old_input.two_factor_code'));
        $this->post(route('two-factor.verify'), ['two_factor_code' => '123456'])->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_regenerar_e_desativar_exigem_password_e_codigo_sem_afetar_outra_conta(): void
    {
        $user = $this->user();
        $other = $this->user();
        $factor = $this->enabled($user);
        $otherFactor = $this->enabled($other);
        $this->actingAs($user)->withSession([TwoFactorManager::VERIFIED => $factor->version]);
        $this->delete(route('admin.profile.two-factor.disable'), [
            'current_password' => 'incorreta', 'two_factor_code' => $this->code($factor),
        ])->assertSessionHasErrors('current_password');
        $this->delete(route('admin.profile.two-factor.disable'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => 'incorreto',
        ])->assertSessionHasErrors('two_factor_code');
        $response = $this->post(route('admin.profile.two-factor.regenerate'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => $this->code($factor),
            'user_id' => $other->id,
        ])->assertOk();
        $codes = $response->viewData('codes');
        $this->assertCount(8, $codes);
        $this->delete(route('admin.profile.two-factor.disable'), [
            'current_password' => 'Atual-segura-123', 'two_factor_code' => $codes[0], 'user_id' => $other->id,
        ])->assertRedirect(route('admin.profile.edit'));
        $this->assertNull($factor->fresh());
        $this->assertNotNull($otherFactor->fresh());
    }

    public function test_login_sem_2fa_mantem_comportamento_anterior_e_password_errada_falha(): void
    {
        $user = $this->user();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'incorreta'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    private function user(): User
    {
        return User::factory()->create(['password' => Hash::make('Atual-segura-123')]);
    }

    public function test_cookie_de_sessao_persistente_nao_contorna_o_segundo_fator(): void
    {
        $user = $this->user();
        $this->enabled($user);
        $user->setRememberToken(str_repeat('r', 60));
        $user->save();
        $cookie = Auth::guard('web')->getRecallerName();
        $value = $user->id.'|'.$user->getRememberToken().'|'.$user->password;

        $this->withCookie($cookie, $value)->get(route('admin.profile.edit'))
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertSame($user->id, session(PendingTwoFactorLogin::KEY.'.id'));
    }

    public function test_novos_codigos_de_recuperacao_invalidam_todos_os_anteriores(): void
    {
        $user = $this->user();
        $factor = app(TwoFactorManager::class)->begin($user);
        $manager = app(TwoFactorManager::class);
        $old = $manager->confirm($user, $factor->version, $this->code($factor));
        $new = $manager->manage($user, $old[0], false);

        $this->assertNull($manager->verify($user, $old[1]));
        $this->assertNotNull($manager->verify($user, $new[0]));
        $this->assertNull($manager->verify($user, $new[0]));
    }

    public function test_codigo_de_outra_conta_e_id_manipulado_nao_autenticam(): void
    {
        $user = $this->user();
        $this->enabled($user);
        $other = $this->user();
        $factor = app(TwoFactorManager::class)->begin($other);
        $codes = app(TwoFactorManager::class)->confirm($other, $factor->version, $this->code($factor));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        $this->post(route('two-factor.verify'), ['two_factor_code' => $codes[0], 'user_id' => $other->id])
            ->assertSessionHasErrors('two_factor_code');
        $this->assertGuest();
        $this->assertCount(8, $factor->fresh()->recovery_codes);
    }

    public function test_cancelar_desafio_impede_autenticacao_posterior(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        $this->delete(route('two-factor.cancel'))->assertRedirect(route('login'));
        $this->assertNull(session(PendingTwoFactorLogin::KEY));
        $this->post(route('two-factor.verify'), ['two_factor_code' => $this->code($factor)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_csrf_protege_configuracao_e_desafio(): void
    {
        $this->app->instance('env', 'production');
        $this->post(route('two-factor.verify'), ['two_factor_code' => '123456'])->assertStatus(419);
        $this->actingAs($this->user())->post(route('admin.profile.two-factor.begin'), [
            'current_password' => 'Atual-segura-123',
        ])->assertStatus(419);
        $this->assertDatabaseCount('cms_two_factor_authentications', 0);
    }

    public function test_mudar_ip_nao_remove_limite_de_tentativas_da_conta(): void
    {
        $user = $this->user();
        $this->enabled($user);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Atual-segura-123']);
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$attempt])
                ->post(route('two-factor.verify'), ['two_factor_code' => 'incorreto'])
                ->assertSessionHasErrors('two_factor_code');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.6'])
            ->post(route('two-factor.verify'), ['two_factor_code' => 'incorreto'])->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_eliminacao_da_conta_remove_a_configuracao_2fa(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        app(DeleteUser::class)->handle($user);
        $this->assertNull($factor->fresh());
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_falha_de_auditoria_na_eliminacao_preserva_configuracao_2fa(): void
    {
        $user = $this->user();
        $factor = $this->enabled($user);
        $this->mock(ActivityLoggerContract::class)->shouldReceive('log')->once()
            ->andThrow(new RuntimeException('Falha de auditoria de teste.'));

        try {
            app(DeleteUser::class)->handle($user);
            $this->fail('A falha de auditoria deve reverter a eliminação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha de auditoria de teste.', $exception->getMessage());
        }

        $this->assertNotNull($factor->fresh());
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    private function begin(User $user): TwoFactorAuthentication
    {
        $this->actingAs($user)->post(route('admin.profile.two-factor.begin'), ['current_password' => 'Atual-segura-123'])
            ->assertRedirect(route('admin.profile.edit'))->assertSessionHasNoErrors();
        return app(TwoFactorManager::class)->query($user)->firstOrFail();
    }

    private function enabled(User $user): TwoFactorAuthentication
    {
        $factor = app(TwoFactorManager::class)->begin($user);
        $factor->confirmed_at = now();
        $factor->expires_at = null;
        $factor->save();
        return $factor;
    }

    private function code(TwoFactorAuthentication $factor): string
    {
        return app(Google2FA::class)->getCurrentOtp($factor->secret);
    }
}
