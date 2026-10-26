<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Pcteckserv\CmsCore\Support\ComposerCommand;
use Pcteckserv\CmsCore\Updates\GitTagUpdateChecker;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeployedPluginConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_updates_replace_old_root_constraints_for_every_plugin(): void
    {
        $files = new Filesystem;
        $originalBase = base_path();
        $composer = base_path('composer.phar');
        $root = storage_path('framework/testing/plugin-constraints-'.bin2hex(random_bytes(6)));
        $site = $root.'/site';
        $versions = ['ecommerce' => ['0.25.0', '0.26.1'], 'agenda' => ['0.13.0', '0.15.0'], 'easypay' => ['0.1.0', '0.2.0'], 'moloni' => ['0.4.0', '0.5.0'], 'paypal' => ['0.1.0', '0.2.0'], 'portfolio' => ['1.13.5', '1.14.0'], 'contact-forms' => ['1.2.0', '1.3.3']];
        $require = [];
        $repositories = [];
        foreach ($versions as $slug => [$old, $new]) {
            $package = 'pcteckserv/cms-'.$slug;
            $require[$package] = '^'.$old;
            foreach (['bundled' => $old, 'source' => $new] as $kind => $version) {
                $directory = $site.'/'.$kind.'/'.$slug;
                $files->ensureDirectoryExists($directory);
                $files->put($directory.'/composer.json', json_encode(['name' => $package], JSON_THROW_ON_ERROR));
                $files->put($directory.'/cms-plugin.json', json_encode(['version' => $version], JSON_THROW_ON_ERROR));
            }
            $repositories[] = ['type' => 'path', 'url' => 'bundled/'.$slug, 'options' => ['symlink' => false, 'versions' => [$package => $old]]];
        }
        $repositories[] = ['packagist.org' => false];
        $files->put($site.'/composer.json', json_encode(['name' => 'tests/site', 'require' => $require, 'repositories' => $repositories], JSON_THROW_ON_ERROR));
        try {
            $install = new Process([PHP_BINARY, $composer, 'install', '--no-interaction', '--no-scripts', '--no-plugins'], $site);
            $install->setEnv(['COMPOSER_HOME' => $root.'/composer-home']);
            $install->mustRun();
            $this->app->setBasePath($site);
            config(['cms-core.updates.composer_binary' => $composer, 'cms-core.updates.github_token' => null]);
            $repository = $this->mock(PluginRepository::class);
            foreach ($versions as $slug => [$old, $new]) {
                $package = 'pcteckserv/cms-'.$slug;
                $plugin = InstalledPlugin::create(['slug' => $slug, 'name' => $package, 'package' => $package, 'label' => $slug, 'installed_version' => $old, 'metadata' => ['repository_type' => 'path']]);
                $source = new AvailablePlugin($slug, $slug, $package, $slug, null, null, '*@dev', $site.'/source/'.$slug, $new);
                $repository->shouldReceive('find')->with($slug)->twice()->andReturn($source);
                $updater = new class(new GitTagUpdateChecker, null, new ComposerCommand) extends PackageUpdater
                {
                    protected function run(array $command): Process
                    {
                        if (in_array('artisan', $command, true)) {
                            $process = Mockery::mock(Process::class);
                            $process->shouldReceive('isSuccessful')->andReturn(true);

                            return $process;
                        }

                        return parent::run($command);
                    }
                };
                $result = $updater->update($package);
                $this->assertTrue($result->successful, $slug.': '.$result->message);
                $manifest = json_decode($files->get($site.'/composer.json'), true);
                $this->assertSame('*@dev', $manifest['require'][$package]);
                $this->assertSame($new, json_decode($files->get($site.'/vendor/'.$package.'/cms-plugin.json'), true)['version']);
                $this->assertSame($new, $plugin->fresh()->installed_version);
            }
        } finally {
            $this->app->setBasePath($originalBase);
            $files->deleteDirectory($root);
        }
    }
}
