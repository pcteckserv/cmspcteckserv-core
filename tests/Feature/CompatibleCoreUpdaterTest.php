<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\CompatibleCoreUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

final class CompatibleCoreUpdaterTest extends TestCase
{
    private function fixture(callable $check, string $require = '^1.0', bool $deployment = false, ?string $releaseMode = null): void
    {
        $original = base_path(); $storage = storage_path();
        $directory = $original.'/tests/.runtime/core-stage-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($directory.'/storage/framework/cache');
        File::ensureDirectoryExists($directory.'/bootstrap/cache');
        $composer = getenv('CMS_COMPOSER_BINARY');
        $this->assertTrue(is_string($composer) && is_file($composer));
        $core = static fn ($version, $requirements) => ['name'=>'pcteckserv/cms-core', 'version'=>$version, 'type'=>'library', 'require'=>$requirements, 'autoload'=>['psr-4'=>['Pcteckserv\\CmsCore\\'=>'src/']]];
        foreach (['3.0.1', '3.1.5', ...($releaseMode !== null ? ['3.1.7'] : [])] as $version) {
            $zip = new ZipArchive(); $zip->open($directory.'/'.$version.'.zip', ZipArchive::CREATE);
            $contents = ['composer.json'=>json_encode($core($version, ['tests/other'=>$version === '3.0.1' ? '^1.0' : $require])), 'src/CmsCoreServiceProvider.php'=>'<?php namespace Pcteckserv\\CmsCore; class CmsCoreServiceProvider {}'];
            foreach ($contents as $file=>$bytes) $zip->addFromString($file,$bytes);
            if ($version === '3.1.7' && $releaseMode !== 'missing') {
                $hashes = array_map(static fn ($bytes)=>hash('sha256',$bytes),$contents);
                if ($releaseMode === 'changed') $hashes['composer.json'] = str_repeat('a',64);
                $zip->addFromString('core-release.json',json_encode(['schema'=>1,'version'=>$version,'files'=>$hashes]));
            }
            $zip->close();
            $packages[] = $core($version, ['tests/other'=>$version === '3.0.1' ? '^1.0' : $require]) + ['dist'=>['type'=>'zip', 'url'=>str_replace('\\', '/', $directory.'/'.$version.'.zip'), 'reference'=>$version]];
        }
        $packages[] = ['name'=>'tests/other','version'=>'1.0.0','type'=>'metapackage'];
        $packages[] = ['name'=>'tests/other','version'=>'2.0.0','type'=>'metapackage'];
        $packages[] = ['name'=>'laravel/framework','version'=>'13.36.0','type'=>'metapackage'];
        if ($deployment) {
            File::ensureDirectoryExists($directory.'/development/plugin');
            File::put($directory.'/development/plugin/composer.json', '{"name":"tests/path-plugin","type":"library","require":{"pcteckserv/cms-core":"^3.0"}}');
            File::put($directory.'/development/plugin/installed-marker.txt', 'actual-installed-code');
        }
        File::put($directory.'/composer.json', json_encode(['name'=>'tests/site', 'require'=>['pcteckserv/cms-core'=>'3.0.1','laravel/framework'=>'^13.0'], 'repositories'=>[['type'=>'package','package'=>$packages],['packagist.org'=>false]]], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        if ($deployment) {
            $manifest = json_decode(File::get($directory.'/composer.json'), true);
            $manifest['require']['tests/path-plugin'] = '1.0.0';
            array_unshift($manifest['repositories'], ['type'=>'path','url'=>'development/plugin','options'=>['symlink'=>false,'versions'=>['tests/path-plugin'=>'1.0.0']]]);
            File::put($directory.'/composer.json', json_encode($manifest));
        }
        File::put($directory.'/artisan', '<?php if(file_exists(__DIR__."/fail-migration") && in_array("migrate",$argv,true)){fwrite(STDERR,"migration test failure");exit(1);} exit(0);');
        $seed = new Process([PHP_BINARY,$composer,'update','--no-scripts','--no-plugins','--no-interaction','--no-audit'], $directory, ['COMPOSER_DISABLE_NETWORK'=>'1','COMPOSER_HOME'=>$directory.'/composer-home']);
        $seed->setTimeout(60)->mustRun();
        $manifest = json_decode(File::get($directory.'/composer.json'), true);
        foreach ($manifest['repositories'] as &$repository) if (($repository['type'] ?? '') === 'package') $repository['package'][] = ['name'=>'laravel/framework','version'=>'13.42.0','type'=>'metapackage'];
        unset($repository);
        File::put($directory.'/composer.json', json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        if ($deployment) {
            File::deleteDirectory($directory.'/development');
            $installed = json_decode(File::get($directory.'/vendor/composer/installed.json'), true);
            foreach ($installed['packages'] as &$package) {
                if ($package['name'] === 'laravel/framework') $package['version'] = '13.42.0';
                if ($package['name'] === 'tests/path-plugin') $package['dist']['url'] = 'packages/tests/path-plugin';
                if ($package['name'] === 'tests/path-plugin') $package['transport-options'] = ['relative'=>true];
            }
            unset($package);
            File::put($directory.'/vendor/composer/installed.json', json_encode($installed));
        }
        config(['cms-core.updates.composer_binary'=>$composer,'cms-core.updates.allow_core_updates'=>true,'cms-core.updates.github_token'=>null]);
        $this->app->setBasePath($directory); $this->app->useStoragePath($directory.'/storage');
        try { $check($directory, new CompatibleCoreUpdater()); }
        finally { $this->app->setBasePath($original); $this->app->useStoragePath($storage); File::deleteDirectory($directory); }
    }

    public function test_real_composer_stages_core_without_upgrading_laravel_or_other_packages(): void
    {
        $this->fixture(function ($directory, $updater) {
            File::put($directory.'/.env', 'test-only-sentinel');
            $result = $updater->update('3.1.5');
            $this->assertTrue($result->successful, $result->message);
            $lock = json_decode(File::get($directory.'/composer.lock'), true);
            $versions = array_column($lock['packages'], 'version', 'name');
            $this->assertSame('3.1.5', $versions['pcteckserv/cms-core']);
            $this->assertSame('13.36.0', $versions['laravel/framework']);
            $this->assertSame('1.0.0', $versions['tests/other']);
            $this->assertSame('test-only-sentinel', File::get($directory.'/.env'));
            $this->assertFileDoesNotExist($directory.'/storage/framework/maintenance.php');
            $this->assertCount(1, glob($directory.'/storage/app/core-updates/*/previous-vendor'));
            $this->assertStringNotContainsString('"type": "package"', File::get($directory.'/composer.lock'));
            $this->assertSame('3.1.5', json_decode(File::get($directory.'/composer.json'), true)['require']['pcteckserv/cms-core']);
        });
    }

    public function test_verified_release_is_installed_with_real_composer(): void
    {
        $this->fixture(function ($directory,$updater) {
            $result=$updater->update('3.1.7');
            $this->assertTrue($result->successful,$result->message);
            $this->assertTrue((new \Pcteckserv\CmsCore\Support\ReleaseIntegrity())->verify($directory.'/vendor/pcteckserv/cms-core','3.1.7')['valid']);
        },releaseMode:'valid');
    }

    public function test_incomplete_or_changed_new_release_never_replaces_live_vendor(): void
    {
        foreach (['missing','changed'] as $mode) {
            $this->fixture(function ($directory,$updater) {
                $before=[File::get($directory.'/composer.json'),File::get($directory.'/composer.lock'),File::get($directory.'/vendor/composer/installed.json')];
                $result=$updater->update('3.1.7');
                $this->assertFalse($result->successful);
                $this->assertStringContainsString('íntegra',$result->message);
                $this->assertSame($before,[File::get($directory.'/composer.json'),File::get($directory.'/composer.lock'),File::get($directory.'/vendor/composer/installed.json')]);
                $this->assertFileDoesNotExist($directory.'/storage/framework/maintenance.php');
            },releaseMode:$mode);
        }
    }

    public function test_dependency_conflict_preserves_live_files_before_installation(): void
    {
        $this->fixture(function ($directory, $updater) {
            $before = [File::get($directory.'/composer.json'), File::get($directory.'/composer.lock'), hash_file('sha256',$directory.'/vendor/composer/installed.json')];
            $result = $updater->update('3.1.5');
            $this->assertFalse($result->successful);
            $this->assertStringContainsString('ficheiros da instalação foram preservados', $result->message);
            $this->assertSame($before, [File::get($directory.'/composer.json'),File::get($directory.'/composer.lock'),hash_file('sha256',$directory.'/vendor/composer/installed.json')]);
            $this->assertFileDoesNotExist($directory.'/storage/framework/maintenance.php');
        }, '^2.0');
    }

    public function test_failed_migration_restores_previous_code_and_composer_metadata(): void
    {
        $this->fixture(function ($directory, $updater) {
            File::put($directory.'/fail-migration', 'fixture');
            $before = [File::get($directory.'/composer.json'),File::get($directory.'/composer.lock'),hash_file('sha256',$directory.'/vendor/composer/installed.json')];
            $result = $updater->update('3.1.5');
            $this->assertFalse($result->successful);
            $this->assertStringContainsString('ficheiros anteriores foram repostos', $result->message);
            $this->assertSame($before, [File::get($directory.'/composer.json'),File::get($directory.'/composer.lock'),hash_file('sha256',$directory.'/vendor/composer/installed.json')]);
            $this->assertFileDoesNotExist($directory.'/storage/framework/maintenance.php');
        });
    }

    public function test_major_update_is_refused_before_any_composer_process(): void
    {
        $this->fixture(function ($directory, $updater) {
            $result = $updater->update('4.0.0');
            $this->assertFalse($result->successful);
            $this->assertStringContainsString('migração', $result->message);
            $this->assertSame('3.0.1', json_decode(File::get($directory.'/composer.json'), true)['require']['pcteckserv/cms-core']);
        });
    }

    public function test_deployment_updates_core_with_missing_development_paths_and_preserves_actual_installed_versions(): void
    {
        $this->fixture(function ($directory, $updater) {
            $result = $updater->update('3.1.5');
            $this->assertTrue($result->successful, $result->message);
            $versions = array_column(json_decode(File::get($directory.'/composer.lock'), true)['packages'], 'version', 'name');
            $this->assertSame('13.42.0', $versions['laravel/framework']);
            $this->assertSame('1.0.0', $versions['tests/path-plugin']);
            $this->assertSame('actual-installed-code', File::get($directory.'/vendor/tests/path-plugin/installed-marker.txt'));
            $composer = getenv('CMS_COMPOSER_BINARY');
            $install = new Process([PHP_BINARY,$composer,'install','--dry-run','--no-scripts','--no-plugins','--no-interaction'], $directory, ['COMPOSER_DISABLE_NETWORK'=>'1']);
            $install->setTimeout(60)->mustRun();
            $this->assertStringContainsString('Nothing to install', $install->getOutput().$install->getErrorOutput());
        }, deployment: true);
    }

    public function test_deployment_removes_a_plugin_without_development_sources_or_other_version_changes(): void
    {
        $this->fixture(function ($directory, $updater) {
            $result = $updater->remove('tests/path-plugin');
            $this->assertTrue($result->successful, $result->message);
            $versions = array_column(json_decode(File::get($directory.'/composer.lock'), true)['packages'], 'version', 'name');
            $this->assertSame('13.42.0', $versions['laravel/framework']);
            $this->assertSame('3.0.1', $versions['pcteckserv/cms-core']);
            $this->assertArrayNotHasKey('tests/path-plugin', $versions);
            $this->assertDirectoryDoesNotExist($directory.'/vendor/tests/path-plugin');
            $this->assertArrayNotHasKey('tests/path-plugin', json_decode(File::get($directory.'/composer.json'), true)['require']);
        }, deployment: true);
    }

    public function test_removal_refuses_an_installed_dependency_without_changing_live_files(): void
    {
        $this->fixture(function ($directory, $updater) {
            $installed = json_decode(File::get($directory.'/vendor/composer/installed.json'), true);
            foreach ($installed['packages'] as &$package) if ($package['name'] === 'pcteckserv/cms-core') $package['require']['tests/path-plugin'] = '^1.0';
            unset($package);
            File::put($directory.'/vendor/composer/installed.json', json_encode($installed));
            $before = File::get($directory.'/composer.json');
            $result = $updater->remove('tests/path-plugin');
            $this->assertFalse($result->successful);
            $this->assertStringContainsString('outro pacote instalado', $result->message);
            $this->assertSame($before, File::get($directory.'/composer.json'));
            $this->assertFileExists($directory.'/vendor/tests/path-plugin/installed-marker.txt');
        }, deployment: true);
    }

    public function test_removing_a_plugin_keeps_snapshot_core_protection_and_production_mode(): void
    {
        $this->fixture(function ($directory, $updater) {
            $installed = json_decode(File::get($directory.'/vendor/composer/installed.json'), true);
            $installed['dev'] = false;
            foreach ($installed['packages'] as &$package) if ($package['name'] === 'pcteckserv/cms-core') $package['dist'] = ['type'=>'path','url'=>'../absent-core','reference'=>'deployed-snapshot'];
            unset($package);
            File::put($directory.'/vendor/composer/installed.json', json_encode($installed));
            $result = $updater->remove('tests/path-plugin');
            $this->assertTrue($result->successful, $result->message);
            $after = json_decode(File::get($directory.'/vendor/composer/installed.json'), true);
            $this->assertFalse($after['dev']);
            $result = $updater->update('3.1.5');
            $this->assertFalse($result->successful);
            $this->assertStringContainsString('snapshot do Starter', $result->message);
        }, deployment: true);
    }
}
