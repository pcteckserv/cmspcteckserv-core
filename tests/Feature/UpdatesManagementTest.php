<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\StarterPackage;
use Pcteckserv\CmsCore\Updates\UpdateResult;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class TestablePackageUpdater extends PackageUpdater
{
    public array $commands = [];

    protected function run(array $command): Process
    {
        $this->commands[] = $command;
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('isSuccessful')->andReturn(true);
        $process->shouldReceive('getOutput')->andReturn('{}');

        return $process;
    }
}

class TestComposerCommand extends ComposerCommand
{
    public function build(array $arguments): array
    {
        return $arguments;
    }

    public function php(array $arguments): array
    {
        return $arguments;
    }
}

class UpdatesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_usa_versao_instalada_lida_do_composer_em_disco(): void
    {
        config(['cms-core.updates.packages' => ['pcteckserv/cms-core']]);

        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')
            ->once()
            ->with('pcteckserv/cms-core')
            ->andReturn(['version' => '2.3.4']);

        $registry = new PackageVersionRegistry(
            Mockery::mock(GitTagUpdateChecker::class),
            new PluginCatalog(),
            $reader,
        );

        $packages = $registry->sync();
        $corePackage = $packages->firstWhere('name', 'pcteckserv/cms-core');

        $this->assertNotNull($corePackage);
        $this->assertSame('2.3.4', $corePackage->installedVersion);
        $this->assertDatabaseHas('cms_installed_packages', [
            'name' => 'pcteckserv/cms-core',
            'installed_version' => '2.3.4',
        ]);
    }

    public function test_update_falha_se_versao_instalada_continuar_abaixo_da_disponivel(): void
    {
        config(['cms-core.updates.packages' => ['pcteckserv/cms-core']]);
        DB::table('cms_installed_packages')->insert([
            'name' => 'pcteckserv/cms-core',
            'installed_version' => '2.3.3',
            'available_version' => 'v2.3.4',
            'channel' => 'stable',
            'checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->times(3)->andReturn(
            ['version' => '2.3.3', 'dist' => ['type' => 'zip', 'reference' => 'same']],
            ['version' => '2.3.3', 'dist' => ['type' => 'zip', 'reference' => 'same']],
            ['version' => '2.3.3', 'dist' => ['type' => 'zip', 'reference' => 'same']],
        );

        $composerCommand = new TestComposerCommand();

        $updater = new TestablePackageUpdater(
            Mockery::mock(GitTagUpdateChecker::class),
            null,
            $composerCommand,
            $reader,
        );

        $result = $updater->update('pcteckserv/cms-core');

        $this->assertFalse($result->successful);
        $this->assertStringContainsString('continua em 2.3.3', $result->message);
    }

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

    public function test_permite_atualizar_starter_quando_repositorio_esta_configurado(): void
    {
        config(['cms-core.updates.starter.repository' => 'pcteckserv/site-exemplo']);

        $admin = $this->superAdmin();

        $this->mock(PackageUpdater::class)
            ->shouldReceive('update')
            ->once()
            ->with(StarterPackage::NAME)
            ->andReturn(new UpdateResult(true, 'Starter atualizado com sucesso para v1.0.0.'));

        $this->mock(PackageVersionRegistry::class)
            ->shouldReceive('checkRemoteUpdates')
            ->once();

        $this->actingAs($admin)
            ->post(route('admin.updates.run', ['package' => StarterPackage::NAME]))
            ->assertRedirect(route('admin.updates.index'))
            ->assertSessionHas('cms_update_success');

        $status = app(UpdateStatusRepository::class)->get(StarterPackage::NAME);

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
