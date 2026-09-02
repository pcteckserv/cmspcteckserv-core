<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use Tests\TestCase;

class AdminAccessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_de_roles_sincroniza_todas_as_permissoes_registadas(): void
    {
        $admin = $this->superAdmin();

        $this->assertSame(0, Permission::query()->count());

        $this->actingAs($admin)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Logs de atividade')
            ->assertSee('Consentimentos')
            ->assertSee('SEO')
            ->assertSee('SMTP')
            ->assertSee('Comandos Laravel')
            ->assertSee('Manutenção')
            ->assertSee('Pré-visualizar manutenção')
            ->assertSee('Selecionar tudo')
            ->assertSee('data-cms-permission-group-select', false);
    }

    public function test_login_com_sessao_iniciada_redireciona_para_o_painel(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_permissoes_diretas_ficam_ocultas_ate_serem_ativadas(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Ativar permissões diretas')
            ->assertSee('data-cms-direct-permissions-panel hidden', false);
    }

    public function test_formulario_de_utilizador_disponibiliza_roles_predefinidas(): void
    {
        $admin = $this->superAdmin();

        Role::query()->whereIn('key', ['core.admin', 'core.editor'])->delete();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('Editor');
    }

    public function test_formulario_de_utilizador_permite_apenas_uma_role(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('type="radio" name="role_id"', false)
            ->assertDontSee('name="roles[]"', false);
    }

    public function test_formulario_de_utilizador_expoe_permissoes_da_role_para_permissoes_diretas(): void
    {
        app(PermissionSynchronizer::class)->sync();

        $admin = $this->superAdmin();
        $permission = Permission::query()->where('key', 'core.smtp.view')->firstOrFail();
        $role = Role::query()->create(['name' => 'Role com permissões', 'key' => 'core.role_with_permissions']);
        $role->permissions()->sync([$permission->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('data-cms-role-permissions', false)
            ->assertSee('data-permission-ids=\'['.$permission->id.']\'', false);
    }

    public function test_payload_com_multiplas_roles_guarda_apenas_uma(): void
    {
        $admin = $this->superAdmin();
        $firstRole = Role::query()->create(['name' => 'Primeira role', 'key' => 'core.first_role']);
        $secondRole = Role::query()->create(['name' => 'Segunda role', 'key' => 'core.second_role']);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Utilizador Role Única',
                'email' => 'utilizador-role-unica@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'state' => 'active',
                'roles' => [$firstRole->id, $secondRole->id],
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'utilizador-role-unica@example.test')->firstOrFail();

        $this->assertSame([$firstRole->id], $user->cmsRoles()->pluck('cms_roles.id')->all());
    }

    public function test_permissoes_diretas_sao_ignoradas_quando_nao_estao_ativadas(): void
    {
        app(PermissionSynchronizer::class)->sync();

        $admin = $this->superAdmin();
        $permission = Permission::query()->where('key', 'core.smtp.view')->firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Utilizador Teste',
                'email' => 'utilizador@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'state' => 'active',
                'permissions' => [$permission->id],
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'utilizador@example.test')->firstOrFail();

        $this->assertFalse($user->cmsPermissions()->whereKey($permission->id)->exists());
    }

    public function test_permissoes_diretas_sao_guardadas_quando_estao_ativadas(): void
    {
        app(PermissionSynchronizer::class)->sync();

        $admin = $this->superAdmin();
        $permission = Permission::query()->where('key', 'core.smtp.view')->firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Utilizador com Permissão',
                'email' => 'utilizador-permissao@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'state' => 'active',
                'direct_permissions_enabled' => '1',
                'permissions' => [$permission->id],
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'utilizador-permissao@example.test')->firstOrFail();

        $this->assertTrue($user->cmsPermissions()->whereKey($permission->id)->exists());
    }

    public function test_smtp_exige_permissoes_especificas(): void
    {
        app(PermissionSynchronizer::class)->sync();

        $plainUser = User::factory()->create();
        $viewer = $this->userWithPermissions(['core.smtp.view']);
        $tester = $this->userWithPermissions(['core.smtp.test']);

        $this->actingAs($plainUser)->get(route('admin.smtp-settings.edit'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.smtp-settings.edit'))->assertOk();

        $this->actingAs($plainUser)
            ->put(route('admin.smtp-settings.update'), [])
            ->assertForbidden();

        $this->actingAs($tester)
            ->post(route('admin.smtp-settings.test'), ['test_recipient' => 'teste@example.test'])
            ->assertRedirect();
    }

    public function test_comandos_laravel_exigem_permissoes_especificas(): void
    {
        app(PermissionSynchronizer::class)->sync();

        $plainUser = User::factory()->create();
        $viewer = $this->userWithPermissions(['core.laravel-commands.view']);

        $this->actingAs($plainUser)->get(route('admin.laravel-commands.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.laravel-commands.index'))->assertOk();

        $this->actingAs($viewer)
            ->post(route('admin.laravel-commands.run', 'cache-clear'))
            ->assertForbidden();
    }

    public function test_menu_nao_mostra_acessos_sem_permissao(): void
    {
        app(PermissionSynchronizer::class)->sync();

        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('SMTP')
            ->assertDontSee('Comandos Laravel')
            ->assertDontSee('Atualizações');
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function userWithPermissions(array $permissionKeys): User
    {
        $role = Role::query()->create([
            'name' => 'Role de teste',
            'key' => 'core.test_'.strtolower(str_replace(['.', '-'], '_', implode('_', $permissionKeys))),
        ]);

        $role->permissions()->sync(
            Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all()
        );

        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['key' => 'core.super_admin'],
            ['name' => 'Super Admin', 'is_protected' => true],
        );

        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }
}
