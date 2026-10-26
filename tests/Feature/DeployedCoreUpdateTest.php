<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeployedCoreUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployed_core_downloads_new_code_instead_of_reusing_bundled_copy(): void
    {
        $files = new Filesystem;
        $originalBase = base_path();
        $composer = base_path('composer.phar');
        $root = storage_path('framework/testing/deployed-core-'.bin2hex(random_bytes(6)));
        $site = $root.'/site';
        $remote = $root.'/remote';
        $bundled = $site.'/packages/pcteckserv/cms-core';
        $files->ensureDirectoryExists($remote);
        $files->ensureDirectoryExists($bundled);
        $package = json_encode(['name' => 'pcteckserv/cms-core', 'description' => 'Fixture'], JSON_THROW_ON_ERROR);
        $files->put($remote.'/composer.json', $package);
        $files->put($remote.'/marker.txt', 'old code');
        $files->put($bundled.'/composer.json', $package);
        $files->put($bundled.'/marker.txt', 'old code');
        $plugin = $site.'/packages/tests/local-plugin';
        $files->ensureDirectoryExists($plugin);
        $files->put($plugin.'/composer.json', json_encode(['name' => 'tests/local-plugin'], JSON_THROW_ON_ERROR));
        $files->put($plugin.'/marker.txt', 'plugin code');

        try {
            foreach ([['git', 'init'], ['git', 'config', 'user.email', 'tests@example.test'], ['git', 'config', 'user.name', 'Tests'], ['git', 'add', '.'], ['git', 'commit', '-m', 'Old release'], ['git', 'tag', 'v2.14.1']] as $command) {
                (new Process($command, $remote))->mustRun();
            }
            $files->put($remote.'/marker.txt', 'new code');
            foreach ([['git', 'add', '.'], ['git', 'commit', '-m', 'New release'], ['git', 'tag', 'v2.14.2']] as $command) {
                (new Process($command, $remote))->mustRun();
            }
            $files->put($site.'/composer.json', json_encode([
                'name' => 'tests/deployed-site',
                'require' => ['pcteckserv/cms-core' => '^2.14', 'tests/local-plugin' => '1.0.0'],
                'repositories' => [
                    ['type' => 'path', 'url' => 'packages/pcteckserv/cms-core', 'options' => ['symlink' => false, 'versions' => ['pcteckserv/cms-core' => '2.14.1']]],
                    ['type' => 'path', 'url' => 'packages/tests/local-plugin', 'options' => ['symlink' => false, 'versions' => ['tests/local-plugin' => '1.0.0']]],
                    ['packagist.org' => false],
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            $install = new Process([PHP_BINARY, $composer, 'install', '--no-interaction', '--no-scripts', '--no-plugins'], $site);
            $install->setEnv(['COMPOSER_HOME' => $root.'/composer-home']);
            $install->mustRun();
            $this->app->setBasePath($site);
            config([
                'cms-core.updates.composer_binary' => $composer,
                'cms-core.updates.github_token' => null,
                'cms-core.updates.repositories.pcteckserv/cms-core' => $remote,
            ]);
            DB::table('cms_installed_packages')->insert([
                'name' => 'pcteckserv/cms-core', 'installed_version' => '2.14.1',
                'available_version' => 'v2.14.2', 'channel' => 'stable',
            ]);
            $updater = new class(Mockery::mock(GitTagUpdateChecker::class), null, new ComposerCommand) extends PackageUpdater
            {
                protected function run(array $command): Process
                {
                    if (($command[1] ?? null) === 'artisan') {
                        $process = Mockery::mock(Process::class);
                        $process->shouldReceive('isSuccessful')->andReturn(true);

                        return $process;
                    }

                    return parent::run($command);
                }
            };
            $result = $updater->update('pcteckserv/cms-core');
            $this->assertTrue($result->successful, $result->message);
            $this->assertSame('new code', $files->get($site.'/vendor/pcteckserv/cms-core/marker.txt'));
            $installed = (new ComposerInstalledPackageReader)->read('pcteckserv/cms-core');
            $this->assertSame('v2.14.2', $installed['version']);
            $this->assertNotSame('path', $installed['dist']['type'] ?? null);
            $manifest = json_decode($files->get($site.'/composer.json'), true);
            $this->assertSame($remote, array_values($manifest['repositories'])[0]['url']);
            $this->assertSame('old code', $files->get($bundled.'/marker.txt'));
            $this->assertSame('plugin code', $files->get($site.'/vendor/tests/local-plugin/marker.txt'));
            $this->assertSame('1.0.0', (new ComposerInstalledPackageReader)->read('tests/local-plugin')['version']);
        } finally {
            $this->app->setBasePath($originalBase);
            $files->deleteDirectory($root);
        }
    }
}
