<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Tests\TestCase;

class InstalledPluginUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugin_instalado_pelo_painel_entra_no_registo_de_atualizacoes(): void
    {
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginCatalog.php';
        require_once dirname(__DIR__, 2).'/src/Updates/PackageVersionRegistry.php';
        config(['cms-plugins.plugins' => [], 'cms-core.updates.packages' => []]);
        $plugin = InstalledPlugin::query()->create([
            'slug' => 'contact-forms', 'name' => 'pcteckserv/cms-contact-forms',
            'package' => 'pcteckserv/cms-contact-forms', 'label' => 'Contacto',
            'status' => 'disabled', 'installed_version' => 'dev-main',
        ]);
        $catalog = new PluginCatalog();
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
