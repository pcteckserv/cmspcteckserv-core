<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Pcteckserv\CmsCore\Models\ActivityLog;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_visitantes_nao_podem_consultar_nem_alterar_o_perfil(): void
    {
        $this->get(route('admin.profile.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.profile.update'), [])->assertRedirect(route('login'));
    }

    public function test_utilizador_sem_permissoes_de_gestao_pode_consultar_os_seus_dados(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Os meus dados')
            ->assertSee($user->email)
            ->assertSee('name="current_password"', false)
            ->assertDontSee($user->password);
    }

    public function test_altera_apenas_o_nome_sem_alterar_password_email_ou_permissoes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $password = $user->password;
        $verified = $user->email_verified_at;

        $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => 'Nome atualizado', 'email' => $user->email, 'password' => '',
            'id' => $other->id, 'user_id' => $other->id, 'is_admin' => true,
            'roles' => [1], 'permissions' => [1], 'state' => 'inactive',
            'email_verified_at' => null,
        ])->assertRedirect(route('admin.profile.edit'))->assertSessionHasNoErrors();

        $this->assertSame('Nome atualizado', $user->fresh()->name);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertEquals($verified, $user->fresh()->email_verified_at);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertSame(0, $user->cmsRoles()->count());
        $this->assertSame(0, $user->cmsPermissions()->count());
        $this->assertTrue($user->fresh()->isCmsActive());
    }

    public function test_altera_email_com_password_atual_e_remove_verificacao_anterior(): void
    {
        $user = $this->user();
        $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => $user->name, 'email' => 'novo@example.test', 'current_password' => 'Atual-segura-123',
        ])->assertRedirect(route('admin.profile.edit'))->assertSessionHasNoErrors();

        $this->assertSame('novo@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_altera_password_com_confirmacao_sem_expor_segredos_na_auditoria(): void
    {
        $user = $this->user();
        $token = $user->getRememberToken();

        $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => $user->name, 'email' => $user->email,
            'current_password' => 'Atual-segura-123',
            'password' => 'Nova-segura-456', 'password_confirmation' => 'Nova-segura-456',
        ])->assertRedirect(route('admin.profile.edit'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Nova-segura-456', $user->fresh()->password));
        $this->assertNotSame($token, $user->fresh()->getRememberToken());
        $this->assertAuthenticatedAs($user);
        $log = ActivityLog::query()->where('action', 'user.profile_updated')->firstOrFail();
        $this->assertStringNotContainsString('Atual-segura-123', $log->toJson());
        $this->assertStringNotContainsString('Nova-segura-456', $log->toJson());
        $this->assertStringNotContainsString($user->fresh()->password, $log->toJson());
    }

    public function test_rejeita_email_duplicado_invalido_e_nome_vazio(): void
    {
        $user = $this->user();
        $other = User::factory()->create();

        foreach (['invalido', $other->email, str_repeat('a', 256).'@example.test'] as $email) {
            $this->actingAs($user)->put(route('admin.profile.update'), [
                'name' => '', 'email' => $email, 'current_password' => 'Atual-segura-123',
            ])->assertSessionHasErrors(['name', 'email']);
        }

        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_exige_password_atual_correta_para_alterar_email_ou_password(): void
    {
        $user = $this->user();

        foreach (['', 'incorreta'] as $currentPassword) {
            foreach (['email', 'password'] as $field) {
                $this->actingAs($user)->put(route('admin.profile.update'), [
                    'name' => 'Não guardar',
                    'email' => $field === 'email' ? 'novo@example.test' : $user->email,
                    'password' => $field === 'password' ? 'Nova-segura-456' : '',
                    'password_confirmation' => 'Nova-segura-456',
                    'current_password' => $currentPassword,
                ])->assertSessionHasErrors('current_password');
            }
        }

        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertTrue(Hash::check('Atual-segura-123', $user->fresh()->password));
        $this->assertNull(session('_old_input.current_password'));
        $this->assertNull(session('_old_input.password'));
        $this->assertNull(session('_old_input.password_confirmation'));
    }

    public function test_rejeita_password_curta_ou_confirmacao_diferente(): void
    {
        $user = $this->user();

        foreach ([['curta', 'curta'], ['Nova-segura-456', 'diferente']] as [$password, $confirmation]) {
            $this->actingAs($user)->put(route('admin.profile.update'), [
                'name' => $user->name, 'email' => $user->email,
                'current_password' => 'Atual-segura-123',
                'password' => $password, 'password_confirmation' => $confirmation,
            ])->assertSessionHasErrors('password');
        }

        $this->assertTrue(Hash::check('Atual-segura-123', $user->fresh()->password));
    }

    private function user(): User
    {
        return User::factory()->create(['password' => Hash::make('Atual-segura-123')]);
    }

    public function test_nome_e_apresentado_com_escaping(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_atualizacoes_repetidas_sao_limitadas(): void
    {
        $user = $this->user();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($user)->put(route('admin.profile.update'), [
                'name' => $user->name, 'email' => $user->email,
            ])->assertRedirect(route('admin.profile.edit'));
        }

        $this->actingAs($user)->putJson(route('admin.profile.update'), [
            'name' => $user->name, 'email' => $user->email,
        ])->assertTooManyRequests();
    }
}
