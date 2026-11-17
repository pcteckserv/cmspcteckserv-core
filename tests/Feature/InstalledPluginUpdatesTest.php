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

    public function test_stale_catalogue_rows_do_not_appear_in_updates(): void
    {
        config(['cms-core.updates.packages'=>[], 'cms-plugins.plugins'=>[]]);
        \Illuminate\Support\Facades\DB::table('cms_installed_packages')->insert(['name'=>'tests/not-installed','available_version'=>'9.0.0','installed_version'=>null,'channel'=>'stable']);
        $registry=app(PackageVersionRegistry::class);
        $this->assertCount(0,$registry->all());
        $this->assertDatabaseHas('cms_installed_packages',['name'=>'tests/not-installed']);
    }

    public function test_plugin_instalado_so_entra_no_registo_de_atualizacoes_depois_de_ativado(): void
    {
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginCatalog.php';
        require_once dirname(__DIR__, 2).'/src/Updates/PackageVersionRegistry.php';
        config(['cms-plugins.plugins' => [], 'cms-core.updates.packages' => []]);
        $plugin = InstalledPlugin::query()->create([
            'slug' => 'contact-forms', 'name' => 'tests/installed-plugin',
            'package' => 'tests/installed-plugin', 'label' => 'Contacto',
            'status' => 'disabled', 'installed_version' => 'dev-main',
        ]);
        $catalog = new PluginCatalog();
        $this->assertContains($plugin->package, $catalog->packages());
        $checker = $this->mock(GitTagUpdateChecker::class);
        $checker->shouldReceive('latestVersion')->once()->with($plugin->package)->andReturn('v1.0.1');
        $this->assertCount(0, (new PackageVersionRegistry($checker, $catalog))->sync());
        $plugin->update(['status' => 'enabled']);
        $packages = (new PackageVersionRegistry($checker, $catalog))->checkRemoteUpdates();
        $this->assertSame($plugin->package, $packages->sole()->name);
        $this->assertSame('v1.0.1', $packages->sole()->availableVersion);
        $plugin->update(['installed_version' => null]);
        $this->assertNotContains($plugin->package, $catalog->packages());
    }
}
