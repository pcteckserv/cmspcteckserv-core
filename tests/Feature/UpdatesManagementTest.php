<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\UpdateResult;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;
use Tests\TestCase;

class UpdatesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_e_executado_no_pedido_http_sem_enviar_para_queue(): void
    {
        config(['queue.default' => 'database']);

        $admin = $this->superAdmin();

        $this->mock(PackageUpdater::class)
            ->shouldReceive('update')
            ->once()
            ->with('pcteckserv/cms-core')
            ->andReturn(new UpdateResult(true, 'Atualização concluída com sucesso.'));

        $this->mock(PackageVersionRegistry::class)
            ->shouldReceive('checkRemoteUpdates')
            ->once();

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_success');

        $status = app(UpdateStatusRepository::class)->get('pcteckserv/cms-core');

        $this->assertSame('succeeded', $status['state'] ?? null);
    }

    public function test_nao_permite_duas_atualizacoes_do_mesmo_package_em_paralelo(): void
    {
        config(['queue.default' => 'database']);

        $admin = $this->superAdmin();

        $this->mock(PackageUpdater::class)
            ->shouldNotReceive('update');

        app(UpdateStatusRepository::class)->markRunning('pcteckserv/cms-core', $admin->id);

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_error');
    }

    public function test_executa_update_mesmo_quando_queue_esta_em_sync(): void
    {
        config(['queue.default' => 'sync']);

        $admin = $this->superAdmin();

        $this->mock(PackageUpdater::class)
            ->shouldReceive('update')
            ->once()
            ->with('pcteckserv/cms-core')
            ->andReturn(new UpdateResult(true, 'Atualização concluída com sucesso.'));

        $this->mock(PackageVersionRegistry::class)
            ->shouldReceive('checkRemoteUpdates')
            ->once();

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_success');

        $status = app(UpdateStatusRepository::class)->get('pcteckserv/cms-core');

        $this->assertSame('succeeded', $status['state'] ?? null);
    }

    public function test_atualizacoes_exigem_permissao(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertForbidden();
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
