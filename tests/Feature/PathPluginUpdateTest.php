<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\InstalledPackage;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PathPluginUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/src/Updates/PackageUpdater.php';
        require_once dirname(__DIR__, 2).'/src/Updates/InstalledPackage.php';
        parent::setUp();
    }

    public function test_atualiza_origem_e_reinstala_plugin_mesmo_com_versao_dev_igual(): void
    {
        $plugin = $this->plugin();
        $this->repository();
        $updater = $this->updater(true);
        $result = $updater->update($plugin->package);
        $this->assertTrue($result->successful);
        $this->assertSame('v1.0.1', $plugin->fresh()->metadata['last_applied_release']);
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
        $source = new AvailablePlugin('test-plugin', 'test-plugin', 'tests/plugin', 'Teste', null, null, '*@dev', '/source');
        $this->mock(PluginRepository::class)->shouldReceive('find')->once()->with('test-plugin')->andReturn($source);
    }

    private function updater(bool $reinstallSuccessful): PackageUpdater
    {
        $updater = Mockery::mock(PackageUpdater::class, [app(GitTagUpdateChecker::class)])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $commands = [];
        $updater->shouldReceive('run')->times($reinstallSuccessful ? 6 : 3)
            ->andReturnUsing(function (array $command) use (&$commands, $reinstallSuccessful): Process {
                $commands[] = $command[1];
                $expected = ['show', 'update', 'reinstall', 'show', 'artisan', 'artisan'];
                $this->assertSame($expected[count($commands) - 1], $command[1]);
                $process = Mockery::mock(Process::class);
                $process->shouldReceive('isSuccessful')->andReturn($command[1] !== 'reinstall' || $reinstallSuccessful);
                $process->shouldReceive('getOutput')->andReturn(json_encode(['versions' => ['dev-main'], 'dist' => ['type' => 'path']]));

                return $process;
            });

        return $updater;
    }
}
