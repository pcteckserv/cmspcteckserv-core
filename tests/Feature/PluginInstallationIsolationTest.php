<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PluginInstallationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(callable $check, string $requiresCore='^3.0'): void
    {
        $original = base_path(); $storage = storage_path();
        $root = $original.'/tests/.runtime/plugin-install-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/storage/framework/cache');
        $composer = getenv('CMS_COMPOSER_BINARY');
        $this->assertFileExists($composer);
        $packages = [['name'=>'pcteckserv/cms-core','version'=>'3.0.1','type'=>'metapackage'], ['name'=>'laravel/framework','version'=>'13.36.0','type'=>'metapackage']];
        $manifest = ['name'=>'tests/site','require'=>['pcteckserv/cms-core'=>'^3.0','laravel/framework'=>'^13.0'], 'repositories'=>[['type'=>'package','package'=>$packages],['packagist.org'=>false]]];
        File::put($root.'/composer.json',json_encode($manifest));
        File::put($root.'/artisan','<?php exit(0);');
        File::put($root.'/.env','private-sentinel');
        (new Process([PHP_BINARY,$composer,'update','--no-scripts','--no-plugins','--no-interaction','--no-audit'],$root,['COMPOSER_DISABLE_NETWORK'=>'1','COMPOSER_HOME'=>$root.'/composer-home']))->setTimeout(60)->mustRun();
        $manifest['repositories'][0]['package'] = [...$packages, ['name'=>'pcteckserv/cms-core','version'=>'3.1.7','type'=>'metapackage'], ['name'=>'laravel/framework','version'=>'13.42.0','type'=>'metapackage'], ['name'=>'tests/new-plugin','version'=>'1.0.0','type'=>'metapackage','require'=>['pcteckserv/cms-core'=>$requiresCore]]];
        File::put($root.'/composer.json',json_encode($manifest));
        config(['cms-core.updates.composer_binary'=>$composer,'cms-core.updates.github_token'=>null]);
        $this->app->setBasePath($root); $this->app->useStoragePath($root.'/storage');
        try { $check($root, new PluginInstaller()); }
        finally { $this->app->setBasePath($original); $this->app->useStoragePath($storage); File::deleteDirectory($root); }
    }

    public function test_real_plugin_install_keeps_core_and_laravel_versions(): void
    {
        $this->fixture(function ($root,$installer) {
            $result=$installer->install(['package'=>'tests/new-plugin','slug'=>'new-plugin','label'=>'Test plugin','version_constraint'=>'1.0.0','version'=>'1.0.0']);
            $this->assertTrue($result->successful,$result->message);
            $versions=array_column(json_decode(File::get($root.'/composer.lock'),true)['packages'],'version','name');
            $this->assertSame('3.0.1',$versions['pcteckserv/cms-core']);
            $this->assertSame('13.36.0',$versions['laravel/framework']);
            $this->assertSame('1.0.0',$versions['tests/new-plugin']);
            $this->assertSame('private-sentinel',File::get($root.'/.env'));
        });
    }

    public function test_incompatible_plugin_is_refused_without_changing_the_installation(): void
    {
        $this->fixture(function ($root,$installer) {
            $before=[File::get($root.'/composer.json'),File::get($root.'/composer.lock'),File::get($root.'/vendor/composer/installed.json')];
            $result=$installer->install(['package'=>'tests/new-plugin','slug'=>'new-plugin','label'=>'Test plugin','version_constraint'=>'1.0.0','version'=>'1.0.0']);
            $this->assertFalse($result->successful);
            $this->assertSame($before,[File::get($root.'/composer.json'),File::get($root.'/composer.lock'),File::get($root.'/vendor/composer/installed.json')]);
            $this->assertFalse(InstalledPlugin::where('slug','new-plugin')->exists());
        },'^4.0');
    }
}
