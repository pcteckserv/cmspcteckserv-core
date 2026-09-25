<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Plugins\InstalledPluginVersionReader;
use Tests\TestCase;

class InstalledPluginVersionReaderTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/src/Plugins/InstalledPluginVersionReader.php';
        parent::setUp();
    }

    public function test_le_manifesto_fisicamente_instalado(): void
    {
        $originalBasePath = $this->app->basePath();
        $directory = sys_get_temp_dir().'/cms-installed-plugin-'.bin2hex(random_bytes(8));
        $manifestDirectory = $directory.'/vendor/tests/plugin';
        File::ensureDirectoryExists($manifestDirectory);
        File::put($manifestDirectory.'/cms-plugin.json', json_encode([
            'version' => '1.2.2',
        ], JSON_THROW_ON_ERROR));

        try {
            $this->app->setBasePath($directory);

            $this->assertSame('1.2.2', (new InstalledPluginVersionReader())->read('tests/plugin'));
        } finally {
            $this->app->setBasePath($originalBasePath);
            File::deleteDirectory($directory);
        }
    }

    public function test_rejeita_package_e_manifesto_invalidos(): void
    {
        $this->assertNull((new InstalledPluginVersionReader())->read('../plugin'));
        $this->assertNull((new InstalledPluginVersionReader())->read('tests/inexistente'));
    }
}
