<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Pcteckserv\CmsCore\Plugins\InstalledPluginVersionReader;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Updates\InstalledPackage;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PathPluginUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/src/Support/ComposerRepositoryCleaner.php';
        require_once dirname(__DIR__, 2).'/src/Support/ComposerCommand.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/DTOs/AvailablePlugin.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginRepository.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/InstalledPluginVersionReader.php';
        require_once dirname(__DIR__, 2).'/src/Updates/GitTagUpdateChecker.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginInstaller.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginManager.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginCatalog.php';
        require_once dirname(__DIR__, 2).'/src/Updates/PackageVersionRegistry.php';
        require_once dirname(__DIR__, 2).'/src/Updates/PackageUpdater.php';
        require_once dirname(__DIR__, 2).'/src/Updates/InstalledPackage.php';
        parent::setUp();
        config(['cms-core.updates.starter.repository' => null]);
    }

    public function test_atualiza_origem_e_reinstala_plugin_mesmo_com_versao_dev_igual(): void
    {
        $plugin = $this->plugin();
        $this->repository();
        $updater = $this->updater(true);
        $result = $updater->update($plugin->package);
        $this->assertTrue($result->successful);
        $this->assertSame('1.0.1', $plugin->fresh()->metadata['version']);
        $this->assertSame('1.0.1', $plugin->fresh()->installed_version);
        $package = new InstalledPackage($plugin->package, 'dev-main', 'v1.0.1', 'stable', null, 'v1.0.1');
        $this->assertFalse($package->hasUpdate());
        $next = new InstalledPackage($plugin->package, 'dev-main', 'v1.0.2', 'stable', null, 'v1.0.1');
        $this->assertTrue($next->hasUpdate());
    }

    public function test_falha_de_reinstalacao_nao_marca_release_como_aplicada(): void
    {
        $plugin = $this->plugin();
        $this->repository();
        $result = $this->updater(false)->update($plugin->package);
        $this->assertFalse($result->successful);
        $this->assertArrayNotHasKey('last_applied_release', $plugin->fresh()->metadata);
    }

    public function test_nao_marca_release_quando_ficheiros_reinstalados_continuam_antigos(): void
    {
        $plugin = $this->plugin();
        $this->repository();
        $result = $this->updater(true, '1.0.0')->update($plugin->package);

        $this->assertFalse($result->successful);
        $this->assertStringContainsString('instalada: 1.0.0; disponível: 1.0.1', $result->message);
        $this->assertArrayNotHasKey('last_applied_release', $plugin->fresh()->metadata);
        $this->assertSame('dev-main', $plugin->fresh()->installed_version);
    }

    public function test_versao_disponivel_do_plugin_vem_dos_metadados_sem_tags_git(): void
    {
        $plugin = $this->plugin();
        $this->repository();
        $this->assertSame('1.0.1', (new GitTagUpdateChecker())->latestVersion($plugin->package));
    }

    public function test_instalacao_nova_guarda_a_versao_dos_metadados(): void
    {
        $installer = Mockery::mock(\Pcteckserv\CmsCore\Plugins\PluginInstaller::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $process = Mockery::mock(Process::class);
        $process->shouldReceive('isSuccessful')->andReturn(true);
        $installer->shouldReceive('run')->times(5)->andReturn($process);
        $source = new AvailablePlugin('test-plugin', 'test-plugin', 'tests/plugin', 'Teste', null, null, '*@dev', '/source', '1.0.2');
        $this->assertTrue($installer->install($source->installData())->successful);
        $plugin = InstalledPlugin::query()->where('slug', 'test-plugin')->firstOrFail();
        $this->assertSame('1.0.2', $plugin->installed_version);
        $this->assertSame('1.0.2', $plugin->metadata['version']);
    }

    public function test_sincronizacao_e_atualizacoes_preservam_a_versao_do_painel(): void
    {
        $plugin = $this->plugin();
        $plugin->update([
            'package' => 'pcteckserv/cms-core',
            'installed_version' => '1.0.2',
            'metadata' => ['repository_type' => 'path', 'version' => '1.0.2'],
        ]);
        config([
            'cms-plugins.plugins' => ['test-plugin' => ['package' => $plugin->package, 'label' => 'Teste']],
            'cms-core.updates.packages' => [],
        ]);
        $catalog = new \Pcteckserv\CmsCore\Plugins\PluginCatalog();
        $manager = new \Pcteckserv\CmsCore\Plugins\PluginManager($catalog);
        $this->assertSame('1.0.2', $manager->all()->sole()->installed_version);
        $checker = $this->mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->twice()->with($plugin->package)
            ->andReturn('1.0.2', '1.0.3');
        $registry = new \Pcteckserv\CmsCore\Updates\PackageVersionRegistry($checker, $catalog);
        $current = $registry->checkRemoteUpdates()->firstWhere('name', $plugin->package);
        $this->assertSame('1.0.2', $current->installedVersion);
        $this->assertFalse($current->hasUpdate());
        $next = $registry->checkRemoteUpdates()->firstWhere('name', $plugin->package);
        $this->assertSame('1.0.2', $next->installedVersion);
        $this->assertSame('1.0.3', $next->availableVersion);
        $this->assertTrue($next->hasUpdate());
    }

    public function test_gestor_reconcilia_versao_declarada_com_manifesto_instalado(): void
    {
        $plugin = InstalledPlugin::query()->create([
            'slug' => 'test-plugin',
            'name' => 'pcteckserv/cms-core',
            'package' => 'pcteckserv/cms-core',
            'label' => 'Teste',
            'status' => 'enabled',
            'installed_version' => '1.2.2',
            'metadata' => [
                'repository_type' => 'path',
                'version' => '1.2.2',
                'last_applied_release' => '1.2.2',
            ],
        ]);
        config(['cms-plugins.plugins' => [
            'test-plugin' => ['package' => 'pcteckserv/cms-core', 'label' => 'Teste'],
        ]]);
        $reader = Mockery::mock(InstalledPluginVersionReader::class);
        $reader->shouldReceive('read')->once()->with('pcteckserv/cms-core')->andReturn('1.1.3');

        $manager = new \Pcteckserv\CmsCore\Plugins\PluginManager(
            new \Pcteckserv\CmsCore\Plugins\PluginCatalog(),
            $reader,
        );
        $manager->sync();

        $plugin->refresh();
        $this->assertSame('1.1.3', $plugin->installed_version);
        $this->assertSame('1.1.3', $plugin->metadata['version']);
        $this->assertSame('1.1.3', $plugin->metadata['last_applied_release']);
    }

    public function test_registo_de_atualizacoes_prefere_manifesto_instalado_a_metadados(): void
    {
        $plugin = InstalledPlugin::query()->create([
            'slug' => 'test-plugin',
            'name' => 'tests/plugin',
            'package' => 'tests/plugin',
            'label' => 'Teste',
            'status' => 'enabled',
            'installed_version' => '1.2.2',
            'metadata' => [
                'repository_type' => 'path',
                'version' => '1.2.2',
                'last_applied_release' => '1.2.2',
            ],
        ]);
        config(['cms-plugins.plugins' => [], 'cms-core.updates.packages' => []]);
        $checker = $this->mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->once()->with($plugin->package)->andReturn('1.2.2');
        $versionReader = Mockery::mock(InstalledPluginVersionReader::class);
        $versionReader->shouldReceive('read')->twice()->with($plugin->package)->andReturn('1.1.3');

        $packages = (new \Pcteckserv\CmsCore\Updates\PackageVersionRegistry(
            $checker,
            new \Pcteckserv\CmsCore\Plugins\PluginCatalog(),
            null,
            $versionReader,
        ))->checkRemoteUpdates();

        $package = $packages->sole();
        $this->assertSame('1.1.3', $package->installedVersion);
        $this->assertSame('1.2.2', $package->availableVersion);
        $this->assertTrue($package->hasUpdate());
    }

    public function test_metadados_invalidos_nao_sao_aceites_como_versao(): void
    {
        $directory = sys_get_temp_dir().'/cms-plugin-metadata-'.bin2hex(random_bytes(8));
        \Illuminate\Support\Facades\File::makeDirectory($directory);
        try {
            $method = new \ReflectionMethod(PluginRepository::class, 'fromDirectory');
            foreach (['1.0.2', 'dev-main', 12] as $version) {
                \Illuminate\Support\Facades\File::put($directory.'/cms-plugin.json', json_encode([
                    'slug' => 'test-plugin', 'package' => 'tests/plugin', 'version' => $version,
                ], JSON_THROW_ON_ERROR));
                $source = $method->invoke(new PluginRepository(), $directory, 'cms-plugin.json');
                if ($version === '1.0.2') {
                    $this->assertSame($version, $source->version);
                    $this->assertSame($version, $source->installData()['version']);
                } else {
                    $this->assertNull($source);
                }
            }
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory($directory);
        }
    }

    private function plugin(): InstalledPlugin
    {
        DB::table('cms_installed_packages')->insert([
            'name' => 'tests/plugin', 'installed_version' => 'dev-main',
            'available_version' => 'v1.0.1', 'channel' => 'stable',
        ]);

        return InstalledPlugin::query()->create([
            'slug' => 'test-plugin', 'name' => 'tests/plugin', 'package' => 'tests/plugin',
            'label' => 'Teste', 'status' => 'enabled', 'installed_version' => 'dev-main',
            'metadata' => ['repository_type' => 'path'],
        ]);
    }

    private function repository(): void
    {
        $source = new AvailablePlugin('test-plugin', 'test-plugin', 'tests/plugin', 'Teste', null, null, '*@dev', '/source', '1.0.1');
        $this->mock(PluginRepository::class)->shouldReceive('find')->once()->with('test-plugin')->andReturn($source);
    }

    private function updater(bool $reinstallSuccessful, string $manifestVersion = '1.0.1'): PackageUpdater
    {
        $reader = Mockery::mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->times($reinstallSuccessful && $manifestVersion === '1.0.1' ? 2 : 1)
            ->with('tests/plugin')->andReturn(['version' => 'dev-main', 'dist' => ['type' => 'path']]);
        $composer = Mockery::mock(ComposerCommand::class);
        $composer->shouldReceive('build')->andReturnUsing(fn (array $arguments) => ['composer', ...$arguments]);
        $composer->shouldReceive('php')->andReturnUsing(fn (array $arguments) => ['php', ...$arguments]);
        $versionReader = Mockery::mock(InstalledPluginVersionReader::class);
        $versionReader->shouldReceive('read')->times($reinstallSuccessful ? 1 : 0)
            ->with('tests/plugin')->andReturn($manifestVersion);
        $updater = Mockery::mock(PackageUpdater::class, [
            app(GitTagUpdateChecker::class),
            null,
            $composer,
            $reader,
            $versionReader,
        ])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $commands = [];
        $updater->shouldReceive('run')->times($reinstallSuccessful && $manifestVersion === '1.0.1' ? 3 : 1)
            ->andReturnUsing(function (array $command) use (&$commands, $reinstallSuccessful): Process {
                $commands[] = $command[1];
                $expected = ['reinstall', 'artisan', 'artisan'];
                $this->assertSame($expected[count($commands) - 1], $command[1]);
                $process = Mockery::mock(Process::class);
                $process->shouldReceive('isSuccessful')->andReturn($command[1] !== 'reinstall' || $reinstallSuccessful);
                $process->shouldReceive('getOutput')->andReturn(json_encode(['versions' => ['dev-main'], 'dist' => ['type' => 'path']]));
                $process->shouldReceive('getErrorOutput')->andReturn('');

                return $process;
            });

        return $updater;
    }
}
