<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Tests\TestCase;

class InstalledPluginUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_versions_are_detected_for_plugins_with_missing_repository_metadata(): void
    {
        $plugin = InstalledPlugin::create(['slug' => 'agenda', 'name' => 'pcteckserv/cms-agenda', 'package' => 'pcteckserv/cms-agenda', 'label' => 'Agenda', 'status' => 'enabled', 'installed_version' => '0.13.0', 'metadata' => []]);
        $source = new AvailablePlugin('agenda', 'cmspcteckserv-agenda', $plugin->package, 'Agenda', null, null, '*@dev', '/fixture', '0.15.0');
        $this->mock(PluginRepository::class)->shouldReceive('find')->with('agenda')->once()->andReturn($source);
        $this->assertSame('0.15.0', (new GitTagUpdateChecker)->latestVersion($plugin->package));
    }

    public function test_plugin_instalado_pelo_painel_entra_no_registo_de_atualizacoes(): void
    {
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginCatalog.php';
        require_once dirname(__DIR__, 2).'/src/Updates/PackageVersionRegistry.php';
        config(['cms-plugins.plugins' => [], 'cms-core.updates.packages' => [], 'cms-core.updates.starter.repository' => null]);
        $plugin = InstalledPlugin::query()->create([
            'slug' => 'updates-fixture', 'name' => 'tests/updates-fixture',
            'package' => 'tests/updates-fixture', 'label' => 'Plugin de teste',
            'status' => 'disabled', 'installed_version' => 'dev-main',
        ]);
        $catalog = new PluginCatalog;
        $this->assertContains($plugin->package, $catalog->packages());
        $checker = $this->mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->once()->with($plugin->package)->andReturn('v1.0.1');
        $packages = (new PackageVersionRegistry($checker, $catalog))->checkRemoteUpdates();
        $this->assertSame($plugin->package, $packages->sole()->name);
        $this->assertSame('v1.0.1', $packages->sole()->availableVersion);
        $plugin->update(['installed_version' => null]);
        $this->assertNotContains($plugin->package, $catalog->packages());
    }
}
