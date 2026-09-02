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
            ->assertSee('Comandos Laravel');
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
