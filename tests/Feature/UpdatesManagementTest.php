<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\StarterPackage;
use Pcteckserv\CmsCore\Updates\StarterUpdater;
use Pcteckserv\CmsCore\Updates\UpdateResult;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class TestablePackageUpdater extends PackageUpdater
{
    public array $commands = [];

    public array $pathRepositoryVersions = [];

    public bool $pathRepositoryPreparedBeforeFirstCommand = false;

    protected function updateComposerPathRepositoryVersion(string $package, string $availableVersion): bool
    {
        $this->pathRepositoryVersions[$package] = $availableVersion;

        return true;
    }

    protected function run(array $command): Process
    {
        if ($this->commands === []) {
            $this->pathRepositoryPreparedBeforeFirstCommand = $this->pathRepositoryVersions !== [];
        }

        $this->commands[] = $command;
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('isSuccessful')->andReturn(true);
        $process->shouldReceive('getOutput')->andReturn('{}');

        return $process;
    }
}

class TestableStarterUpdater extends StarterUpdater
{
    public function copyFiles(string $source, string $destination): void
    {
        $this->copyStarterFiles($source, $destination);
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

class TestGitTagUpdateChecker extends GitTagUpdateChecker
{
    public array $sources = [];

    protected function latestGitVersion(string $repository, mixed $token): ?string
    {
        $this->sources[] = 'git';

        return 'v2.3.9';
    }

    protected function latestGithubVersion(string $repository, mixed $token): ?string
    {
        $this->sources[] = 'api';

        return 'v2.3.8';
    }

    public function processEnvironment(): array
    {
        return $this->gitProcess(['git', '--version'])->getEnv();
    }
}

class UpdatesManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_http_update_reads_real_metadata_and_updates_path_manifest(): void
    {
        $admin = $this->superAdmin();
        $originalBase = base_path();
        $directory = sys_get_temp_dir().'/cms-http-update-'.bin2hex(random_bytes(8));
        $files = new \Illuminate\Filesystem\Filesystem();
        $files->makeDirectory($directory.'/vendor/composer', 0755, true);
        $package = ['name' => 'pcteckserv/cms-core', 'version' => '2.3.10', 'dist' => ['type' => 'path']];
        $files->put($directory.'/vendor/composer/installed.json', json_encode(['packages' => [$package]]));
        $files->put($directory.'/composer.json', json_encode(['repositories' => [[
            'type' => 'path', 'url' => '../core',
            'options' => ['versions' => ['pcteckserv/cms-core' => '2.3.10']],
        ]]]));
        DB::table('cms_installed_packages')->insert([
            'name' => 'pcteckserv/cms-core', 'installed_version' => '2.3.10',
            'available_version' => 'v2.3.11', 'channel' => 'stable',
        ]);
        $updater = new class(Mockery::mock(GitTagUpdateChecker::class), null, new TestComposerCommand()) extends PackageUpdater {
            public array $commands = [];

            protected function run(array $command): Process
            {
                $this->commands[] = $command;
                if ($command[0] === 'update') {
                    $manifest = json_decode(file_get_contents(base_path('composer.json')), true);
                    $path = base_path('vendor/composer/installed.json');
                    $installed = json_decode(file_get_contents($path), true);
                    $installed['packages'][0]['version'] = $manifest['repositories'][0]['options']['versions']['pcteckserv/cms-core'];
                    file_put_contents($path, json_encode($installed));
                }
                $process = Mockery::mock(Process::class);
                $process->shouldReceive('isSuccessful')->andReturn(true);

                return $process;
            }
        };
        $this->app->instance(PackageUpdater::class, $updater);
        $registry = new PackageVersionRegistry(Mockery::mock(GitTagUpdateChecker::class), new PluginCatalog());
        $this->mock(PackageVersionRegistry::class)->shouldReceive('checkRemoteUpdates')->once()
            ->andReturnUsing(fn () => $registry->sync());

        try {
            $this->app->setBasePath($directory);
            $this->actingAs($admin)
                ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
                ->assertSessionHas('cms_update_success')
                ->assertSessionMissing('cms_update_error');
            $this->assertCount(3, $updater->commands);
            $this->assertDatabaseHas('cms_installed_packages', [
                'name' => 'pcteckserv/cms-core', 'installed_version' => '2.3.11',
            ]);
            $this->assertSame('2.3.11', (new ComposerInstalledPackageReader())->read('pcteckserv/cms-core')['version']);
        } finally {
            $this->app->setBasePath($originalBase);
            $files->deleteDirectory($directory);
        }
    }

    public function test_http_update_fails_when_installed_metadata_cannot_be_read(): void
    {
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->once()->andReturn([]);
        $updater = new TestablePackageUpdater(
            Mockery::mock(GitTagUpdateChecker::class), null, new TestComposerCommand(), $reader,
        );
        $this->app->instance(PackageUpdater::class, $updater);
        $this->mock(PackageVersionRegistry::class)->shouldReceive('checkRemoteUpdates')->once();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.updates.run', ['package' => 'pcteckserv/cms-core']))
            ->assertSessionHas('cms_update_error')
            ->assertSessionMissing('cms_update_success');

        $this->assertSame([], $updater->commands);
        $this->assertSame('failed', app(UpdateStatusRepository::class)->get('pcteckserv/cms-core')['state']);
    }

    public function test_update_fails_when_composer_succeeds_but_final_metadata_is_missing(): void
    {
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->twice()->andReturn(
            ['version' => '2.3.10', 'dist' => ['type' => 'zip']], [],
        );
        $checker = Mockery::mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->andReturn('v2.3.11');
        $updater = new TestablePackageUpdater($checker, null, new TestComposerCommand(), $reader);

        $this->assertFalse($updater->update('pcteckserv/cms-core')->successful);
        $this->assertCount(1, $updater->commands);
    }

    public function test_verificacao_de_tags_prefere_git_remoto_a_api_do_github(): void
    {
        config([
            'cms-core.updates.repositories.pcteckserv/cms-core' => 'https://github.com/pcteckserv/cmspcteckserv-core.git',
            'cms-core.updates.github_token' => 'token-de-teste',
        ]);

        $checker = new TestGitTagUpdateChecker();

        $this->assertSame('v2.3.9', $checker->latestVersion('pcteckserv/cms-core'));
        $this->assertSame(['git'], $checker->sources);
    }

    public function test_verificacao_de_tags_nao_abre_pedidos_interativos_de_credenciais(): void
    {
        $environment = (new TestGitTagUpdateChecker())->processEnvironment();

        $this->assertSame('0', $environment['GIT_TERMINAL_PROMPT'] ?? null);
        $this->assertSame('Never', $environment['GCM_INTERACTIVE'] ?? null);
    }

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

    public function test_update_prepara_versao_do_repositorio_path_antes_de_executar_composer(): void
    {
        DB::table('cms_installed_packages')->insert([
            'name' => 'pcteckserv/cms-core',
            'installed_version' => '2.3.6',
            'available_version' => 'v2.3.7',
            'channel' => 'stable',
            'checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->twice()->andReturn(
            ['version' => '2.3.6', 'dist' => ['type' => 'path', 'reference' => 'old']],
            ['version' => '2.3.7', 'dist' => ['type' => 'path', 'reference' => 'new']],
        );

        $updater = new TestablePackageUpdater(
            Mockery::mock(GitTagUpdateChecker::class),
            null,
            new TestComposerCommand(),
            $reader,
        );

        $result = $updater->update('pcteckserv/cms-core');

        $this->assertTrue($result->successful);
        $this->assertTrue($updater->pathRepositoryPreparedBeforeFirstCommand);
        $this->assertSame(['pcteckserv/cms-core' => 'v2.3.7'], $updater->pathRepositoryVersions);
        $this->assertSame(['update', 'pcteckserv/cms-core', '--with-dependencies'], $updater->commands[0]);
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

    public function test_starter_updater_inclui_autenticacao_quando_token_esta_configurado(): void
    {
        config([
            'cms-core.updates.starter.github_token' => 'meu-token-secreto',
        ]);

        $updater = new StarterUpdater();
        $command = $updater->cloneCommand('https://github.com/pcteckserv/studioranco.git', 'v0.1.0', '/tmp/destination');

        $expectedAuth = base64_encode('x-access-token:meu-token-secreto');
        $this->assertContains('-c', $command);
        $this->assertContains('http.https://github.com/.extraheader=AUTHORIZATION: basic '.$expectedAuth, $command);
        $this->assertContains('clone', $command);
        $this->assertContains('--branch', $command);
        $this->assertContains('v0.1.0', $command);
    }

    public function test_starter_updater_nao_inclui_autenticacao_sem_token(): void
    {
        config([
            'cms-core.updates.starter.github_token' => null,
            'cms-core.updates.github_token' => null,
        ]);

        $updater = new StarterUpdater();
        $command = $updater->cloneCommand('https://github.com/pcteckserv/studioranco.git', 'v0.1.0', '/tmp/destination');

        $this->assertNotContains('-c', $command);
        $this->assertContains('clone', $command);
    }

    public function test_starter_updater_environment_nao_duplica_cabecalho_de_autenticacao(): void
    {
        config([
            'cms-core.updates.starter.github_token' => 'meu-token-secreto',
        ]);

        $updater = new StarterUpdater();
        $env = $updater->environment();

        $this->assertArrayNotHasKey('GIT_CONFIG_COUNT', $env);
        $this->assertArrayNotHasKey('GIT_CONFIG_KEY_0', $env);
        $this->assertArrayNotHasKey('GIT_CONFIG_VALUE_0', $env);
        $this->assertSame('0', $env['GIT_TERMINAL_PROMPT'] ?? null);
    }

    public function test_starter_updater_copia_assets_para_public_path_personalizado(): void
    {
        $temporaryRoot = storage_path('framework/testing/starter-public-path-'.bin2hex(random_bytes(6)));
        $source = $temporaryRoot.'/source';
        $destination = $temporaryRoot.'/application';
        $customPublicPath = $temporaryRoot.'/public_html';
        $originalPublicPath = public_path();

        File::ensureDirectoryExists($source.'/public/build');
        File::ensureDirectoryExists($source.'/public/images');
        File::ensureDirectoryExists($source.'/public/storage');
        File::put($source.'/public/build/manifest.json', '{"site.css":"site.css"}');
        File::put($source.'/public/images/logo.png', 'logo');
        File::put($source.'/public/storage/private.txt', 'private');

        try {
            $this->app->usePublicPath($customPublicPath);

            (new TestableStarterUpdater())->copyFiles($source, $destination);

            $this->assertFileExists($destination.'/public/build/manifest.json');
            $this->assertFileExists($customPublicPath.'/build/manifest.json');
            $this->assertFileExists($customPublicPath.'/images/logo.png');
            $this->assertFileDoesNotExist($customPublicPath.'/storage/private.txt');
        } finally {
            $this->app->usePublicPath($originalPublicPath);
            File::deleteDirectory($temporaryRoot);
        }
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
