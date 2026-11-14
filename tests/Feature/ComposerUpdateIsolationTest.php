<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class ComposerUpdateIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_composer_keeps_framework_and_other_locked_packages_while_updating_core(): void
    {
        $composer = getenv('CMS_COMPOSER_BINARY');
        $this->assertTrue(is_string($composer) && is_file($composer), 'Defina CMS_COMPOSER_BINARY para executar o ensaio isolado.');
        $original = base_path();
        $directory = $original.'/tests/.runtime/composer-isolation-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($directory.'/storage/framework/cache');
        $package = static fn ($name, $version, $require = []) => ['name'=>$name, 'version'=>$version, 'type'=>'metapackage', 'require'=>(object) $require];
        $baseline = [
            $package('laravel/framework', '13.36.0'), $package('tests/other', '1.0.0'),
            $package('pcteckserv/cms-core', '3.0.1', ['laravel/framework'=>'^13.0', 'tests/other'=>'^1.0']),
        ];
        $manifest = ['name'=>'tests/isolated-site', 'require'=>['laravel/framework'=>'^13.0', 'pcteckserv/cms-core'=>'^3.0'], 'repositories'=>[['type'=>'package', 'package'=>$baseline], ['packagist.org'=>false]], 'config'=>['allow-plugins'=>false]];
        $write = static fn ($value) => File::put($directory.'/composer.json', json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $write($manifest);
        File::put($directory.'/artisan', '<?php exit(0);');
        try {
            $seed = new Process([PHP_BINARY, $composer, 'update', '--no-interaction', '--no-scripts', '--no-plugins', '--no-audit'], $directory, ['COMPOSER_DISABLE_NETWORK'=>'1', 'COMPOSER_HOME'=>$directory.'/composer-home']);
            $seed->setTimeout(60)->mustRun();
            $before = json_decode(File::get($directory.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
            $baseline[] = $package('laravel/framework', '13.42.0');
            $baseline[] = $package('tests/other', '1.1.0');
            $baseline[] = $package('composer/semver', '3.6.0');
            $baseline[] = $package('pcteckserv/cms-core', '3.1.3', ['laravel/framework'=>'^13.0', 'tests/other'=>'^1.0', 'composer/semver'=>'^3.4']);
            $manifest['repositories'][0]['package'] = $baseline;
            $write($manifest);
            DB::table('cms_installed_packages')->insert(['name'=>'pcteckserv/cms-core', 'installed_version'=>'3.0.1', 'available_version'=>'3.1.3', 'channel'=>'stable']);
            config(['cms-core.updates.composer_binary'=>$composer, 'cms-core.updates.github_token'=>null]);
            $this->app->setBasePath($directory);
            $updater = new class(Mockery::mock(GitTagUpdateChecker::class)) extends PackageUpdater {
                protected function run(array $command): Process
                {
                    $process = new Process($command, base_path(), ['COMPOSER_DISABLE_NETWORK'=>'1', 'COMPOSER_HOME'=>base_path('composer-home')]);
                    $process->setTimeout(60)->run();
                    return $process;
                }
            };
            $result = $updater->update('pcteckserv/cms-core');
            $this->assertTrue($result->successful, $result->message);
            $after = json_decode(File::get($directory.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
            $versions = static fn ($lock) => collect($lock['packages'])->pluck('version', 'name')->all();
            $this->assertSame('13.36.0', $versions($before)['laravel/framework']);
            $this->assertSame('13.36.0', $versions($after)['laravel/framework']);
            $this->assertSame('1.0.0', $versions($after)['tests/other']);
            $this->assertSame('3.1.3', $versions($after)['pcteckserv/cms-core']);
            $this->assertSame('3.6.0', $versions($after)['composer/semver']);
        } finally {
            $this->app->setBasePath($original);
            File::deleteDirectory($directory);
        }
    }
}
