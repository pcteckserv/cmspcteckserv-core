<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\StarterUpdater;
use RuntimeException;
use Tests\TestCase;

final class StarterDependencyPreservationTest extends TestCase
{
    public function test_starter_keeps_independently_updated_core_and_rejects_new_plugin_dependencies(): void
    {
        $root = base_path('tests/.runtime/starter-deps-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($root.'/source'); File::ensureDirectoryExists($root.'/destination/vendor/composer');
        $manifest = ['name'=>'tests/site','require'=>['pcteckserv/cms-core'=>'3.0.1','tests/plugin'=>'^1.0']];
        File::put($root.'/source/composer.json', json_encode($manifest));
        File::put($root.'/source/composer.lock', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.0.1"}]}');
        File::put($root.'/destination/vendor/composer/installed.json', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.1.5"}]}');
        $manifest['require']['pcteckserv/cms-core'] = '3.1.5';
        File::put($root.'/destination/composer.json', $original = json_encode($manifest));
        File::put($root.'/destination/composer.lock', 'keep-lock'); File::put($root.'/source/sentinel', 'new-content');
        $updater = new class extends StarterUpdater { public function copy($source,$destination): void { $this->copyStarterFiles($source,$destination); } };
        try {
            $updater->copy($root.'/source',$root.'/destination');
            $this->assertSame($original, File::get($root.'/destination/composer.json'));
            $this->assertSame('keep-lock', File::get($root.'/destination/composer.lock'));
            $this->assertSame('new-content', File::get($root.'/destination/sentinel'));
            File::delete($root.'/destination/sentinel');
            $manifest['require']['tests/plugin'] = '^2.0'; File::put($root.'/source/composer.json', json_encode($manifest));
            try { $updater->copy($root.'/source',$root.'/destination'); $this->fail('A alteração de dependências tinha de ser recusada.'); }
            catch (RuntimeException $error) { $this->assertStringContainsString('dependências', $error->getMessage()); }
            $this->assertFileDoesNotExist($root.'/destination/sentinel');
            $this->assertSame($original, File::get($root.'/destination/composer.json'));
        } finally { File::deleteDirectory($root); }
    }
}
