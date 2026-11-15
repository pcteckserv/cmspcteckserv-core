<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Tests\TestCase;

class EmptyPluginCatalogTest extends TestCase
{
    public function test_a_new_site_without_plugin_repository_has_an_empty_catalog(): void
    {
        $original=storage_path();
        $root=$original.'/framework/testing/catalog-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root);
        $this->app->useStoragePath($root);
        config(['cms-plugins.repository_url'=>'','cms-plugins.local_repository'=>null]);
        try {
            $this->assertTrue((new PluginRepository())->available()->isEmpty());
            $this->assertDirectoryExists($root.'/framework/cache/cms-plugin-repository');
        } finally {
            $this->app->useStoragePath($original);File::deleteDirectory($root);
        }
    }
}
