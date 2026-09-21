<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Support\ComposerRepositoryCleaner;
use Tests\TestCase;

class ComposerRepositoryCleanerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/src/Support/ComposerRepositoryCleaner.php';

        parent::setUp();
    }

    public function test_remove_apenas_repositorios_path_inexistentes(): void
    {
        $originalBasePath = $this->app->basePath();
        $directory = sys_get_temp_dir().'/cms-composer-cleaner-'.bin2hex(random_bytes(8));
        $validRepository = $directory.'/packages/plugin';
        File::makeDirectory($validRepository, 0755, true);
        File::put($directory.'/composer.json', json_encode([
            'name' => 'tests/site',
            'repositories' => [
                ['name' => 'invalid', 'type' => 'path', 'url' => 'C:\\development\\plugin'],
                ['name' => 'valid', 'type' => 'path', 'url' => 'packages/plugin'],
                ['type' => 'vcs', 'url' => 'https://github.com/pcteckserv/cmspcteckserv-core.git'],
            ],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        try {
            $this->app->setBasePath($directory);
            (new ComposerRepositoryCleaner())->removeInvalidPathRepositories();

            $manifest = json_decode(File::get($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

            $this->assertCount(2, $manifest['repositories']);
            $this->assertSame('valid', $manifest['repositories'][0]['name']);
            $this->assertSame('vcs', $manifest['repositories'][1]['type']);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($directory);
        }
    }

    public function test_recupera_temporariamente_packages_path_instaladas_no_vendor(): void
    {
        $originalBasePath = $this->app->basePath();
        $directory = sys_get_temp_dir().'/cms-composer-recovery-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory.'/vendor/pcteckserv/cms-contact-forms', 0755, true);
        File::put($directory.'/composer.json', json_encode([
            'name' => 'tests/site',
            'repositories' => [],
        ], JSON_THROW_ON_ERROR));
        File::put($directory.'/composer.lock', json_encode([
            'packages' => [[
                'name' => 'pcteckserv/cms-contact-forms',
                'dist' => ['type' => 'path', 'url' => 'C:\\development\\plugin'],
            ]],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->app->setBasePath($directory);
            $cleaner = new ComposerRepositoryCleaner();
            $cleaner->removeInvalidPathRepositories(true);

            $manifest = json_decode(File::get($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame('vendor/pcteckserv/cms-contact-forms', $manifest['repositories'][0]['url']);

            $cleaner->removeInvalidPathRepositories();
            $manifest = json_decode(File::get($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([], $manifest['repositories']);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($directory);
        }
    }
}
