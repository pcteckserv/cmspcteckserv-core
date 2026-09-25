<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use ReflectionMethod;
use Tests\TestCase;

class PluginRepositoryConfigurationTest extends TestCase
{
    public function test_comando_de_permissoes_usado_pelo_instalador_esta_registado(): void
    {
        require_once dirname(__DIR__, 2).'/src/Console/SyncPermissionsCommand.php';

        $this->mock(\Pcteckserv\CmsCore\Services\PermissionSynchronizer::class)
            ->shouldReceive('sync')->once()->andReturn(0);

        $this->artisan(\Pcteckserv\CmsCore\Console\SyncPermissionsCommand::NAME)
            ->assertExitCode(0);
    }

    public function test_composer_configura_repositorio_com_tipo_e_url(): void
    {
        require_once dirname(__DIR__, 2).'/src/Support/ComposerRepositoryCleaner.php';
        require_once dirname(__DIR__, 2).'/src/Support/ComposerCommand.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginInstaller.php';

        $originalBasePath = $this->app->basePath();
        $directory = sys_get_temp_dir().'/cms-plugin-repository-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory, 0755, true);
        File::put($directory.'/composer.json', json_encode(['name' => 'tests/site'], JSON_THROW_ON_ERROR));
        config(['cms-core.updates.github_token' => null]);

        try {
            $this->app->setBasePath($directory);
            $method = new ReflectionMethod(PluginInstaller::class, 'configureRepository');
            $process = $method->invoke(new PluginInstaller(), 'contact-forms', 'vcs', 'https://github.com/pcteckserv/plugin.git');

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $configuration = json_decode(File::get($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $repository = array_values($configuration['repositories'])[0];
            $this->assertSame('vcs', $repository['type']);
            $this->assertSame('https://github.com/pcteckserv/plugin.git', $repository['url']);

            $pathProcess = $method->invoke(
                new PluginInstaller(),
                'contact-forms',
                'path',
                $directory.'/plugin',
                'tests/contact-forms',
                '1.2.6',
            );

            $this->assertTrue($pathProcess->isSuccessful(), $pathProcess->getErrorOutput());
            $configuration = json_decode(File::get($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $repository = array_values(array_filter(
                $configuration['repositories'],
                fn (array $repository): bool => ($repository['type'] ?? null) === 'path',
            ))[0];
            $this->assertSame($directory.'/plugin', $repository['url']);
            $this->assertSame(['tests/contact-forms' => '1.2.6'], $repository['options']['versions']);
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($directory);
        }
    }

    public function test_repositorio_git_completa_clone_shallow_antes_de_atualizar(): void
    {
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginRepository.php';

        $directory = sys_get_temp_dir().'/cms-plugin-shallow-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory.'/.git', 0755, true);
        $method = new ReflectionMethod(PluginRepository::class, 'fetchArguments');

        try {
            File::put($directory.'/.git/shallow', "commit\n");
            $this->assertSame(
                ['git', '-C', $directory, 'fetch', '--unshallow', 'origin'],
                $method->invoke(new PluginRepository(), $directory),
            );

            File::delete($directory.'/.git/shallow');
            $this->assertSame(
                ['git', '-C', $directory, 'fetch', 'origin'],
                $method->invoke(new PluginRepository(), $directory),
            );
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
