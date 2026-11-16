<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PathPluginRealComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_deployed_path_plugin_and_confirms_actual_files_with_real_composer(): void
    {
        $this->updateFixture(false);
    }

    public function test_updates_frozen_snapshot_from_core_deployment_with_real_composer(): void
    {
        $this->updateFixture(true);
    }

    public function test_updates_symlinked_plugin_without_reading_cached_old_target(): void
    {
        $this->updateFixture(false, true);
    }

    private function updateFixture(bool $frozen, bool $symlink = false): void
    {
        $original = base_path();
        $root = $original.'/tests/.runtime/plugin-real-'.bin2hex(random_bytes(6));
        $composer = getenv('CMS_COMPOSER_BINARY');
        $this->assertTrue(is_string($composer) && is_file($composer));
        $json = static fn ($data) => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        foreach (['packages/tests/plugin', 'upstream/plugin', 'bootstrap/cache'] as $folder) File::ensureDirectoryExists($root.'/'.$folder);
        foreach (['packages/tests/plugin' => '1.3.4', 'upstream/plugin' => '2.0.0'] as $folder => $version) {
            File::put($root.'/'.$folder.'/composer.json', $json(['name'=>'tests/plugin', 'type'=>'library']));
            File::put($root.'/'.$folder.'/cms-plugin.json', $json(['slug'=>'test-plugin','package'=>'tests/plugin','version'=>$version]));
            File::put($root.'/'.$folder.'/marker.txt', $version);
        }
        $repository = ['type'=>'path','url'=>'packages/tests/plugin','options'=>['symlink'=>$symlink,'versions'=>['tests/plugin'=>'dev-main']]];
        if ($frozen) {
            $zip = new \ZipArchive();
            $zip->open($root.'/old-plugin.zip', \ZipArchive::CREATE);
            foreach (['composer.json','cms-plugin.json','marker.txt'] as $file) $zip->addFile($root.'/packages/tests/plugin/'.$file, 'plugin/'.$file);
            $zip->close();
            $repository = ['type'=>'package','package'=>['name'=>'tests/plugin','version'=>'dev-main','type'=>'library','extra'=>['cms-original-dist-type'=>'path'],'dist'=>['type'=>'zip','url'=>$root.'/old-plugin.zip','reference'=>'frozen']]];
        }
        File::put($root.'/composer.json', $json([
            'name'=>'tests/host', 'require'=>['tests/plugin'=>'*@dev'],
            'repositories'=>[
                $repository,
                ['packagist.org'=>false],
            ],
        ]));
        File::put($root.'/artisan', '<?php exit(0);');
        try {
            (new Process([PHP_BINARY,$composer,'install','--no-interaction','--no-scripts'], $root))->setTimeout(120)->mustRun();
            $plugin = InstalledPlugin::query()->create([
                'slug'=>'test-plugin','name'=>'tests/plugin','package'=>'tests/plugin','label'=>'Teste',
                'status'=>'enabled','installed_version'=>'1.3.4','metadata'=>['repository_type'=>'path','version'=>'1.3.4'],
            ]);
            $this->mock(PluginRepository::class)->shouldReceive('find')->twice()->with('test-plugin')->andReturn(
                new AvailablePlugin('test-plugin','plugin','tests/plugin','Teste',null,null,'*@dev',$root.'/upstream/plugin','2.0.0')
            );
            $this->app->setBasePath($root);
            config(['cms-core.updates.composer_binary'=>$composer]);
            $result = app(PackageUpdater::class)->update('tests/plugin');
            $this->assertTrue($result->successful, $result->message);
            $this->assertSame('2.0.0', File::get($root.'/vendor/tests/plugin/marker.txt'));
            $this->assertSame('2.0.0', $plugin->fresh()->installed_version);
            $this->assertSame('1.3.4', File::get($root.'/packages/tests/plugin/marker.txt'));
        } finally {
            $this->app->setBasePath($original);
            File::deleteDirectory($root);
        }
    }
}
