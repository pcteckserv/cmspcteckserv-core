<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Pcteckserv\CmsCore\Jobs\UpdatePackageJob;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;
use Tests\TestCase;

class UpdatesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_e_colocado_na_queue_sem_executar_no_pedido_http(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_success');

        Queue::assertPushed(UpdatePackageJob::class, function (UpdatePackageJob $job): bool {
            return $job->package === 'pcteckserv/cms-core';
        });

        $status = app(UpdateStatusRepository::class)->get('pcteckserv/cms-core');

        $this->assertSame('queued', $status['state'] ?? null);
    }

    public function test_nao_permite_duas_atualizacoes_do_mesmo_package_em_paralelo(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);

        $admin = $this->superAdmin();

        app(UpdateStatusRepository::class)->markRunning('pcteckserv/cms-core', $admin->id);

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_error');

        Queue::assertNothingPushed();
    }

    public function test_nao_executa_update_quando_queue_esta_em_sync(): void
    {
        Queue::fake();
        config(['queue.default' => 'sync']);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_error');

        Queue::assertNothingPushed();
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
