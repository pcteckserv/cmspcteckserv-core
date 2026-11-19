<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\StarterDependencyPolicy;
use Tests\TestCase;

class StarterDependencyPolicyTest extends TestCase
{
    public function test_languages_can_be_added_or_removed_without_replacing_installed_dependencies(): void
    {
        $root = storage_path('framework/testing/languages-policy-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($root.'/source');
        File::ensureDirectoryExists($root.'/site/vendor/composer');
        $current = ['name' => 'tests/site', 'require' => ['php' => '^8.3', 'pcteckserv/cms-core' => '^3.5.1']];
        $incoming = $current;
        $incoming['require']['pcteckserv/cms-languages'] = '^0.1.0';
        File::put($root.'/site/vendor/composer/installed.json', $installed = '{"packages":[{"name":"pcteckserv/cms-core","version":"3.5.2"}]}');
        File::put($root.'/site/composer.lock', 'preserved lock');
        File::put($root.'/source/composer.lock', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.5.2"}]}');
        try {
            foreach ([[$current, $incoming], [$incoming, $current]] as [$before, $after]) {
                File::put($root.'/site/composer.json', $bytes = json_encode($before));
                File::put($root.'/source/composer.json', json_encode($after));
                (new StarterDependencyPolicy)->assertCompatible($root.'/source', $root.'/site');
                $this->assertSame($bytes, File::get($root.'/site/composer.json'));
                $this->assertSame('preserved lock', File::get($root.'/site/composer.lock'));
                $this->assertSame($installed, File::get($root.'/site/vendor/composer/installed.json'));
            }
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_newer_source_lock_does_not_force_a_core_upgrade_when_declared_requirements_are_satisfied(): void
    {
        $root = storage_path('framework/testing/starter-policy-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($root.'/source');
        File::ensureDirectoryExists($root.'/site/vendor/composer');
        $manifest = ['name' => 'tests/site', 'require' => ['php' => '^8.3', 'pcteckserv/cms-core' => '^3.1.2']];
        File::put($root.'/site/composer.json', $before = json_encode($manifest));
        File::put($root.'/site/vendor/composer/installed.json', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.4.0"}]}');
        File::put($root.'/source/composer.json', json_encode($manifest));
        File::put($root.'/source/composer.lock', '{"packages":[{"name":"pcteckserv/cms-core","version":"3.5.0"}]}');
        try {
            (new StarterDependencyPolicy)->assertCompatible($root.'/source', $root.'/site');
            $this->assertSame($before, File::get($root.'/site/composer.json'));
            foreach (['^3.5', '^4.0'] as $require) {
                $manifest['require']['pcteckserv/cms-core'] = $require;
                File::put($root.'/source/composer.json', json_encode($manifest));
                try {
                    (new StarterDependencyPolicy)->assertCompatible($root.'/source', $root.'/site');
                    $this->fail('Incompatible Core accepted');
                } catch (\RuntimeException $error) {
                    $this->assertStringContainsString('nenhum ficheiro foi copiado', strtolower($error->getMessage()));
                }
            }
        } finally {
            File::deleteDirectory($root);
        }
    }
}
