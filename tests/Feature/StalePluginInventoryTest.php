<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use Pcteckserv\CmsCore\Plugins\PluginManager;
use Pcteckserv\CmsCore\Updates\ComposerDeploymentTransaction;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Tests\TestCase;

final class StalePluginInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(callable $check): void
    {
        $old = base_path(); $root = $old.'/tests/.runtime/inventory-'.bin2hex(random_bytes(5));
        File::ensureDirectoryExists($root.'/vendor/composer');
        File::put($root.'/composer.json', '{"name":"tests/site","require":{"pcteckserv/cms-core":"3.5.0"}}');
        File::put($root.'/composer.lock', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.5.0"}],"packages-dev":[]}');
        File::put($root.'/vendor/composer/installed.json', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.5.0"}]}');
        $this->app->setBasePath($root);
        try { $check($root); } finally { $this->app->setBasePath($old); File::deleteDirectory($root); }
    }

    private function record(string $status = 'disabled'): InstalledPlugin
    {
        return InstalledPlugin::create(['slug'=>'orphan','name'=>'tests/orphan','package'=>'tests/orphan','label'=>'Antigo','installed_version'=>'0.20.0','status'=>$status,'installed_at'=>now()]);
    }

    public function test_sync_clears_disabled_orphan_version_even_without_loaded_provider(): void
    {
        $plugin = $this->record();
        $this->fixture(function () use ($plugin): void {
            app(PluginManager::class)->sync();
            $this->assertNull($plugin->fresh()->installed_version);
            $this->assertNull($plugin->fresh()->installed_at);
            $this->assertSame('disabled', $plugin->fresh()->status);
        });
    }

    public function test_removing_confirmed_orphan_cleans_only_inventory_and_does_not_execute_composer(): void
    {
        $this->record();
        Schema::create('orphan_content', fn ($t) => $t->string('content'));
        DB::table('orphan_content')->insert(['content'=>'preservado']);
        $this->mock(ComposerDeploymentTransaction::class)->shouldNotReceive('remove');
        $this->fixture(function ($root): void {
            File::ensureDirectoryExists($root.'/vendor/tests/orphan');
            File::put($root.'/vendor/tests/orphan/unmanaged.txt', 'ficheiros antigos preservados');
            $result = app(PluginInstaller::class)->uninstall('orphan');
            $this->assertTrue($result->successful);
            $this->assertStringContainsString('já não estava instalado', $result->message);
            $this->assertDatabaseMissing('cms_plugins', ['slug'=>'orphan']);
            $this->assertDatabaseHas('orphan_content', ['content'=>'preservado']);
            $this->assertSame('ficheiros antigos preservados', File::get($root.'/vendor/tests/orphan/unmanaged.txt'));
        });
    }

    public function test_active_record_is_not_automatically_cleared_or_removed(): void
    {
        $plugin = $this->record('enabled');
        $this->fixture(function () use ($plugin): void {
            app(PluginManager::class)->sync();
            $this->assertSame('0.20.0', $plugin->fresh()->installed_version);
            $this->assertFalse(app(PluginInstaller::class)->uninstall('orphan')->successful);
            $this->assertDatabaseHas('cms_plugins', ['slug'=>'orphan','status'=>'enabled']);
        });
    }

    public function test_inventory_cleanup_requires_consistent_composer_manifests(): void
    {
        foreach (['required','locked','installed','invalid-json','invalid-shape'] as $case) $this->fixture(function ($root) use ($case): void {
            if ($case === 'required') File::put($root.'/composer.json', '{"name":"tests/site","require":{"tests/orphan":"1.0"}}');
            if ($case === 'locked') File::put($root.'/composer.lock', '{"packages":[],"packages-dev":[{"name":"tests/orphan","version":"1.0"}]}');
            if ($case === 'installed') File::put($root.'/vendor/composer/installed.json', '{"packages":[{"name":"tests/orphan","version":"1.0"}]}');
            if ($case === 'invalid-json') File::put($root.'/composer.lock', '{invalid');
            if ($case === 'invalid-shape') File::put($root.'/composer.lock', '{"packages":"invalid"}');
            $this->assertFalse(app(ComposerInstalledPackageReader::class)->isAbsent('tests/orphan'), $case);
        });
    }
}
