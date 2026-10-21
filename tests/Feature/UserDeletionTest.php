<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use RuntimeException;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app('view')->replaceNamespace('cms-core', [realpath(__DIR__.'/../../resources/views')]);
    }

    public function test_super_admin_elimina_utilizador_e_associacoes_com_auditoria(): void
    {
        $admin = $this->superAdmin();
        $target = $this->userWithPermissions();
        $target->cmsState()->create(['state' => 'active']);

        $this->actingAs($admin)->delete(route('admin.users.force-delete', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Utilizador eliminado com sucesso.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        foreach (['cms_role_user', 'cms_permission_user', 'cms_user_states'] as $table) {
            $this->assertDatabaseMissing($table, ['user_id' => $target->id, 'user_type' => $target->getMorphClass()]);
        }
        $this->assertDatabaseHas('cms_activity_logs', [
            'action' => 'user.permanently_deleted',
            'user_id' => $admin->id,
            'subject_id' => $target->id,
            'subject_type' => $target->getMorphClass(),
        ]);
        $this->assertDatabaseHas('cms_roles', ['key' => 'test.user_manager']);
    }

    public function test_visitante_nao_pode_eliminar(): void
    {
        $target = User::factory()->create();

        $this->delete(route('admin.users.force-delete', $target))->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_model_configurado_com_soft_deletes_e_eliminado_definitivamente(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['cms-core.user_model' => DeletableCmsUser::class]);
        $target = DeletableCmsUser::query()->findOrFail(User::factory()->create()->id);
        $target->cmsState()->create(['state' => 'active']);

        $this->actingAs($this->superAdmin())->delete(route('admin.users.force-delete', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('cms_user_states', ['user_id' => $target->id]);
    }

    public function test_relacao_restrita_impede_eliminacao_sem_perder_associacoes(): void
    {
        Schema::create('test_user_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
        });
        $target = $this->userWithPermissions();
        $target->cmsState()->create(['state' => 'active']);
        DB::table('test_user_references')->insert(['user_id' => $target->id]);

        $this->actingAs($this->superAdmin())->delete(route('admin.users.force-delete', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('admin.users.index'))->assertOk()
            ->assertSee('Não foi possível eliminar o utilizador.')
            ->assertDontSee('FOREIGN KEY constraint failed');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertTrue($target->cmsRoles()->exists());
        $this->assertTrue($target->cmsPermissions()->exists());
        $this->assertTrue($target->cmsState()->exists());
    }

    public function test_utilizador_sem_permissoes_nao_pode_eliminar(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create())->delete(route('admin.users.force-delete', $target))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_permissoes_diretas_ou_herdadas_nao_autorizam_eliminacao(): void
    {
        $manager = $this->userWithPermissions();
        $target = User::factory()->create();
        $reserved = Permission::query()->create([
            'key' => 'core.users.force-delete', 'label' => 'Permissão manipulada', 'group' => 'Teste',
        ]);
        $manager->cmsPermissions()->attach($reserved);
        $manager->cmsRoles()->firstOrFail()->permissions()->attach($reserved);

        $this->actingAs($manager);
        $this->assertFalse(Gate::allows('core.users.force-delete'));
        $this->delete(route('admin.users.force-delete', $target))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('cms_activity_logs', ['action' => 'user.permanently_deleted']);
    }

    public function test_super_admin_nao_pode_eliminar_outro_super_admin_nem_a_si_proprio(): void
    {
        $admin = $this->superAdmin();
        $other = $this->superAdmin();

        $this->actingAs($admin);
        foreach ([$admin, $other] as $target) {
            $this->delete(route('admin.users.force-delete', $target))->assertNotFound();
            $this->assertDatabaseHas('users', ['id' => $target->id]);
        }
    }

    public function test_utilizador_inexistente_devolve_404(): void
    {
        $this->actingAs($this->superAdmin())->delete(route('admin.users.force-delete', 999999))->assertNotFound();
    }

    public function test_botao_eliminar_aparece_apenas_para_super_admin(): void
    {
        $target = User::factory()->create();
        $admin = $this->superAdmin();
        $manager = $this->userWithPermissions();

        foreach (['admin.users.index', 'admin.users.edit'] as $route) {
            $url = route($route, $route === 'admin.users.edit' ? $target : []);
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertSee(route('admin.users.force-delete', $target), false)
                ->assertSee('Confirma a eliminação definitiva deste utilizador?');
            $this->actingAs($manager)->get($url)->assertOk()
                ->assertDontSee(route('admin.users.force-delete', $target), false);
        }
    }

    public function test_rota_anterior_mantem_desativacao_sem_eliminar(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWithPermissions())->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertSame('inactive', $target->fresh()->cmsAccessState());
    }

    public function test_falha_de_auditoria_reverte_eliminacao_e_associacoes(): void
    {
        $admin = $this->superAdmin();
        $target = $this->userWithPermissions();
        $target->cmsState()->create(['state' => 'active']);
        $this->mock(ActivityLoggerContract::class)->shouldReceive('log')->once()
            ->andThrow(new RuntimeException('Falha de auditoria de teste.'));

        $this->actingAs($admin)->delete(route('admin.users.force-delete', $target))->assertServerError();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertTrue($target->cmsRoles()->exists());
        $this->assertTrue($target->cmsPermissions()->exists());
        $this->assertTrue($target->cmsState()->exists());
    }

    private function userWithPermissions(): User
    {
        app(PermissionSynchronizer::class)->sync();
        $role = Role::query()->firstOrCreate(['key' => 'test.user_manager'], ['name' => 'Gestor de utilizadores']);
        $role->permissions()->sync(Permission::query()->whereIn('key', [
            'core.users.view', 'core.users.update', 'core.users.delete',
        ])->pluck('id'));
        $user = User::factory()->create();
        $user->cmsRoles()->attach($role);
        $user->cmsPermissions()->attach(Permission::query()->where('key', 'core.users.delete')->firstOrFail());

        return $user;
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['key' => config('cms-core.super_admin_role', 'core.super_admin')],
            ['name' => 'Super Admin', 'is_protected' => true],
        );
        $user = User::factory()->create();
        $user->cmsRoles()->attach($role);

        return $user;
    }
}

class DeletableCmsUser extends User
{
    use SoftDeletes;

    protected $table = 'users';
}
