<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;
use Tests\TestCase;

class ComposerInstalledPackageReaderTest extends TestCase
{
    public function test_reads_installed_metadata_without_composer_environment_and_refreshes_after_update(): void
    {
        $originalBase = base_path();
        $directory = sys_get_temp_dir().'/cms-reader-'.bin2hex(random_bytes(8));
        $environment = [];
        File::ensureDirectoryExists($directory.'/vendor/composer');

        try {
            foreach (['APPDATA', 'HOME', 'COMPOSER_HOME'] as $key) {
                $environment[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }

            $this->app->setBasePath($directory);
            $reader = new ComposerInstalledPackageReader();
            $path = $directory.'/vendor/composer/installed.json';
            foreach (['2.3.10', '2.3.11'] as $version) {
                File::put($path, json_encode(['packages' => [[
                    'name' => 'pcteckserv/cms-core',
                    'version' => $version,
                    'dist' => ['type' => 'path', 'reference' => $version],
                ]]], JSON_THROW_ON_ERROR));

                $package = $reader->read('pcteckserv/cms-core');
                $this->assertSame($version, $package['version'] ?? null);
                $this->assertSame('path', $package['dist']['type'] ?? null);
            }

            $this->assertSame([], $reader->read('missing/package'));
            File::put($path, '{invalid');
            $this->assertSame([], $reader->read('pcteckserv/cms-core'));
            File::delete($path);
            $this->assertSame([], $reader->read('pcteckserv/cms-core'));
        } finally {
            $this->app->setBasePath($originalBase);
            foreach ($environment as $key => [$value, $env, $server]) {
                putenv($value === false ? $key : $key.'='.$value);
                if ($env !== null) {
                    $_ENV[$key] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$key] = $server;
                }
            }
            File::deleteDirectory($directory);
        }
    }
}
