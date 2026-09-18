<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use ReflectionMethod;
use Tests\TestCase;

class PluginRepositoryConfigurationTest extends TestCase
{
    public function test_composer_configura_repositorio_com_tipo_e_url(): void
    {
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
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($directory);
        }
    }
}
