<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use Pcteckserv\CmsCore\Plugins\InstalledPluginSource;
use Pcteckserv\CmsCore\Updates\ComposerDeploymentTransaction;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\UpdateResult;
use Tests\TestCase;

final class DeployedPluginRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_removal_keeps_record_on_failure_and_only_deletes_inventory_after_success(): void
    {
        InstalledPlugin::create(['slug'=>'deployed','name'=>'tests/deployed','package'=>'tests/deployed','label'=>'Distribuído','status'=>'disabled','installed_version'=>'1.0.0']);
        Schema::create('test_plugin_content', fn ($table) => $table->string('content'));
        DB::table('test_plugin_content')->insert(['content'=>'dados a preservar']);
        $this->mock(ComposerDeploymentTransaction::class)->shouldReceive('remove')->twice()->with('tests/deployed')->andReturn(new UpdateResult(false, 'Dependência instalada.'), new UpdateResult(true, 'Removido.'));
        $this->assertFalse(app(PluginInstaller::class)->uninstall('deployed')->successful);
        $this->assertDatabaseHas('cms_plugins', ['slug'=>'deployed']);
        $this->assertTrue(app(PluginInstaller::class)->uninstall('deployed')->successful);
        $this->assertDatabaseMissing('cms_plugins', ['slug'=>'deployed']);
        $this->assertDatabaseHas('test_plugin_content', ['content'=>'dados a preservar']);
    }

    public function test_archived_path_plugins_keep_manifest_version_detection(): void
    {
        $reader = $this->mock(ComposerInstalledPackageReader::class);
        $reader->shouldReceive('read')->once()->andReturn(['dist'=>['type'=>'zip'], 'extra'=>['cms-original-dist-type'=>'path']]);
        $plugin = new InstalledPlugin(['package'=>'tests/deployed']);
        $this->assertTrue((new InstalledPluginSource($reader))->isPath($plugin));
        $plugin->metadata = ['repository_type'=>'git'];
        $this->assertFalse((new InstalledPluginSource($reader))->isPath($plugin));
    }
}
