<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\CompatibleCoreUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

final class CompatibleCoreUpdaterTest extends TestCase
{
    private function fixture(callable $check, string $require = '^1.0', bool $deployment = false, ?string $releaseMode = null, bool $packaged = false, bool $gitDeployment = false): void
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
        if ($gitDeployment) {
            $repository = $directory.'/core-repository';
            File::ensureDirectoryExists($repository.'/src');
            $git = static function (array $arguments) use ($repository): string {
                return trim((new Process(['git', ...$arguments], $repository))->mustRun()->getOutput());
            };
            $git(['init']);
            $git(['config','user.name','Isolated updater test']);
            $git(['config','user.email','test@example.invalid']);
            foreach ($packages as &$candidate) {
                if ($candidate['name'] !== 'pcteckserv/cms-core') continue;
                File::put($repository.'/composer.json',json_encode($core($candidate['version'],$candidate['require'])));
                File::put($repository.'/src/CmsCoreServiceProvider.php','<?php namespace Pcteckserv\\CmsCore; class CmsCoreServiceProvider {}');
                $archive=new ZipArchive(); $archive->open($directory.'/'.$candidate['version'].'.zip');
                $release=$archive->getFromName('core-release.json'); $archive->close();
                if ($release !== false) File::put($repository.'/core-release.json',$release);
                else File::delete($repository.'/core-release.json');
                $git(['add','.']); $git(['commit','-m',$candidate['version']]);
                unset($candidate['dist']);
                $candidate['source']=['type'=>'git','url'=>str_replace('\\','/',$repository),'reference'=>$git(['rev-parse','HEAD'])];
            }
            unset($candidate);
        }
        $packages[] = ['name'=>'tests/other','version'=>'2.0.0','type'=>'metapackage'];
        $packages[] = ['name'=>'laravel/framework','version'=>'13.36.0','type'=>'metapackage'];
        if ($packaged) $packages[] = ['name'=>'tests/removable-plugin','version'=>'1.0.0','type'=>'metapackage'];
        if ($deployment) {
            File::ensureDirectoryExists($directory.'/development/plugin');
            File::put($directory.'/development/plugin/composer.json', '{"name":"tests/path-plugin","type":"library","require":{"pcteckserv/cms-core":"^3.0"}}');
            File::put($directory.'/development/plugin/installed-marker.txt', 'actual-installed-code');
        }
        File::put($directory.'/composer.json', json_encode(['name'=>'tests/site', 'require'=>['pcteckserv/cms-core'=>'3.0.1','laravel/framework'=>'^13.0'], 'repositories'=>[['type'=>'package','package'=>$packages],['packagist.org'=>false]]], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        if ($deployment) {
            $manifest = json_decode(File::get($directory.'/composer.json'), true);
            $manifest['require']['tests/path-plugin'] = '1.0.0';
            if ($packaged) $manifest['require']['tests/removable-plugin'] = '1.0.0';
            array_unshift($manifest['repositories'], ['type'=>'path','url'=>'development/plugin','options'=>['symlink'=>false,'versions'=>['tests/path-plugin'=>'1.0.0']]]);
            File::put($directory.'/composer.json', json_encode($manifest));
        }
        File::put($directory.'/artisan', '<?php if(file_exists(__DIR__."/fail-migration") && in_array("migrate",$argv,true)){fwrite(STDERR,"migration test failure");exit(1);} exit(0);');
        $seed = new Process([PHP_BINARY,$composer,'update','--no-scripts','--no-plugins','--no-interaction','--no-audit'], $directory, ['COMPOSER_DISABLE_NETWORK'=>$gitDeployment ? '0' : '1','COMPOSER_HOME'=>$directory.'/composer-home']);
        $seed->setTimeout(60)->mustRun();
        if ($gitDeployment) {
            $checkout=$directory.'/vendor/pcteckserv/cms-core/.git';
            $this->assertDirectoryExists($checkout);
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($checkout,\FilesystemIterator::SKIP_DOTS)) as $file) {
                // Objetos temporários Git podem desaparecer entre a enumeração e a remoção.
                // A ausência completa de .git é confirmada abaixo.
                if ($file->isFile()) @chmod($file->getPathname(),0666);
            }
            for ($attempt=0;$attempt<20;$attempt++) {
                File::deleteDirectory($checkout); clearstatcache();
                if (!is_dir($checkout)) break;
                usleep(100000);
            }
            $this->assertDirectoryDoesNotExist($checkout);
        }
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
            if ($packaged) {
                File::ensureDirectoryExists($directory.'/packages/tests');
                File::moveDirectory($directory.'/vendor/tests/path-plugin',$directory.'/packages/tests/path-plugin');
            }
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
            File::put($directory.'/vendor/composer/tmp-abcdef123456.zip','incomplete-composer-download');
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
            $this->assertFileDoesNotExist($directory.'/vendor/composer/tmp-abcdef123456.zip');
            $this->assertSame('incomplete-composer-download',File::get(glob($directory.'/storage/app/core-updates/*/previous-vendor')[0].'/composer/tmp-abcdef123456.zip'));
            $this->assertStringNotContainsString('"type": "package"', File::get($directory.'/composer.lock'));
            $this->assertSame('3.1.5', json_decode(File::get($directory.'/composer.json'), true)['require']['pcteckserv/cms-core']);
        });
    }

    public function test_ftp_deployment_without_git_is_reinstalled_only_in_stage(): void
    {
        $this->fixture(function ($directory,$updater) {
            $this->assertDirectoryDoesNotExist($directory.'/vendor/pcteckserv/cms-core/.git');
            $result=$updater->update('3.1.5');
            $this->assertTrue($result->successful,$result->message);
            $versions=array_column(json_decode(File::get($directory.'/vendor/composer/installed.json'),true)['packages'],'version','name');
            $this->assertSame('3.1.5',$versions['pcteckserv/cms-core']);
            $this->assertSame('13.36.0',$versions['laravel/framework']);
            $this->assertSame('1.0.0',$versions['tests/other']);
            $backup=glob($directory.'/storage/app/core-updates/*/previous-vendor')[0];
            $this->assertSame('3.0.1',json_decode(File::get($backup.'/pcteckserv/cms-core/composer.json'),true)['version']);
            $this->assertDirectoryDoesNotExist($backup.'/pcteckserv/cms-core/.git');
            $next=$updater->update('3.1.7');
            $this->assertTrue($next->successful,$next->message);
            $this->assertTrue((new \Pcteckserv\CmsCore\Support\ReleaseIntegrity())->verify($directory.'/vendor/pcteckserv/cms-core','3.1.7')['valid']);
            $this->assertSame('13.36.0',array_column(json_decode(File::get($directory.'/vendor/composer/installed.json'),true)['packages'],'version','name')['laravel/framework']);
        },releaseMode:'valid',gitDeployment:true);
    }

    public function test_source_deployment_conflict_preserves_code_and_metadata(): void
    {
        $this->fixture(function ($directory,$updater) {
            $files=['composer.json','composer.lock','vendor/composer/installed.json','vendor/pcteckserv/cms-core/src/CmsCoreServiceProvider.php'];
            $before=array_map(fn($file)=>File::get($directory.'/'.$file),$files);
            $result=$updater->update('3.1.5');
            $this->assertFalse($result->successful);
            $this->assertSame($before,array_map(fn($file)=>File::get($directory.'/'.$file),$files));
            $this->assertDirectoryDoesNotExist($directory.'/vendor/pcteckserv/cms-core/.git');
            $this->assertFileDoesNotExist($directory.'/storage/framework/maintenance.php');
        },require:'^2.0',gitDeployment:true);
    }

    public function test_plugin_removal_keeps_unchanged_source_core_without_git(): void
    {
        $this->fixture(function ($directory,$updater) {
            $result=$updater->remove('tests/removable-plugin');
            $this->assertTrue($result->successful,$result->message);
            $versions=array_column(json_decode(File::get($directory.'/vendor/composer/installed.json'),true)['packages'],'version','name');
            $this->assertSame('3.0.1',$versions['pcteckserv/cms-core']);
            $this->assertSame('13.42.0',$versions['laravel/framework']);
            $this->assertArrayNotHasKey('tests/removable-plugin',$versions);
            $this->assertDirectoryDoesNotExist($directory.'/vendor/pcteckserv/cms-core/.git');
            $this->assertSame('actual-installed-code',File::get($directory.'/packages/tests/path-plugin/installed-marker.txt'));
        },deployment:true,packaged:true,gitDeployment:true);
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

    public function test_core_update_preserves_plugin_in_packages_with_obsolete_vendor_install_path(): void
    {
        $this->fixture(function ($directory,$updater) {
            $this->assertDirectoryDoesNotExist($directory.'/vendor/tests/path-plugin');
            // Estado reportado: o plugin já exige Core novo, mas o Core antigo ficou instalado.
            $installed=json_decode(File::get($directory.'/vendor/composer/installed.json'),true);
            foreach ($installed['packages'] as &$package) if ($package['name'] === 'tests/path-plugin') $package['require']['pcteckserv/cms-core']='^3.1.0';
            unset($package);
            File::put($directory.'/vendor/composer/installed.json',json_encode($installed));
            $pluginManifest=$directory.'/packages/tests/path-plugin/composer.json';
            $plugin=json_decode(File::get($pluginManifest),true); $plugin['require']['pcteckserv/cms-core']='^3.1.0';
            File::put($pluginManifest,json_encode($plugin));
            $result=$updater->update('3.1.5');
            $this->assertTrue($result->successful,$result->message);
            $this->assertSame('actual-installed-code',File::get($directory.'/vendor/tests/path-plugin/installed-marker.txt'));
            $this->assertSame('actual-installed-code',File::get($directory.'/packages/tests/path-plugin/installed-marker.txt'));
            $versions=array_column(json_decode(File::get($directory.'/composer.lock'),true)['packages'],'version','name');
            $this->assertSame('13.42.0',$versions['laravel/framework']);
            $this->assertSame('1.0.0',$versions['tests/path-plugin']);
        },deployment:true,packaged:true);
    }

    public function test_removing_another_plugin_preserves_the_packaged_plugin_and_core_versions(): void
    {
        $this->fixture(function ($directory,$updater) {
            $result=$updater->remove('tests/removable-plugin');
            $this->assertTrue($result->successful,$result->message);
            $this->assertSame('actual-installed-code',File::get($directory.'/vendor/tests/path-plugin/installed-marker.txt'));
            $versions=array_column(json_decode(File::get($directory.'/composer.lock'),true)['packages'],'version','name');
            $this->assertArrayNotHasKey('tests/removable-plugin',$versions);
            $this->assertSame('3.0.1',$versions['pcteckserv/cms-core']);
            $this->assertSame('13.42.0',$versions['laravel/framework']);
        },deployment:true,packaged:true);
    }

    public function test_316_transaction_with_fixed_snapshot_supports_the_ftp_bridge(): void
    {
        class_alias(\Pcteckserv\CmsCore\Updates\InstalledComposerSnapshot::class,'Tests\\LegacyBridge\\InstalledComposerSnapshot');
        class_alias(\Pcteckserv\CmsCore\Updates\UpdateResult::class,'Tests\\LegacyBridge\\UpdateResult');
        require_once dirname(__DIR__).'/fixtures/updates/ComposerDeploymentTransaction316.php';
        $this->fixture(function ($directory) {
            $updater=new \Tests\LegacyBridge\ComposerDeploymentTransaction();
            $result=$updater->update('3.1.5');
            $this->assertTrue($result->successful,$result->message);
            $result=$updater->remove('tests/removable-plugin');
            $this->assertTrue($result->successful,$result->message);
            $this->assertSame('actual-installed-code',File::get($directory.'/vendor/tests/path-plugin/installed-marker.txt'));
            $versions=array_column(json_decode(File::get($directory.'/composer.lock'),true)['packages'],'version','name');
            $this->assertSame('3.1.5',$versions['pcteckserv/cms-core']);
            $this->assertSame('13.42.0',$versions['laravel/framework']);
            $this->assertArrayNotHasKey('tests/removable-plugin',$versions);
        },deployment:true,packaged:true);
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
