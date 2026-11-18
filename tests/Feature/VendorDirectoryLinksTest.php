<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Pcteckserv\CmsCore\Updates\ComposerDeploymentTransaction;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class VendorDirectoryLinksTest extends TestCase
{
    private array $createdLinks = [];

    private function isDirectoryLink(string $path): bool
    {
        if (is_link($path)) return true;
        $normalize = static fn ($p) => strtolower(rtrim(str_replace('\\', '/', $p), '/'));
        $real = realpath($path); $parent = realpath(dirname($path));
        return PHP_OS_FAMILY === 'Windows' && $real !== false && $parent !== false && $normalize($real) !== $normalize($parent.'/'.basename($path));
    }

    private function directoryLink(string $target, string $link): void
    {
        $created = @symlink($target, $link);
        if (! $created && PHP_OS_FAMILY === 'Windows') {
            $quote = static fn ($path) => "'".str_replace("'", "''", $path)."'";
            (new Process(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', 'New-Item -ItemType Junction -Path '.$quote($link).' -Target '.$quote($target).' | Out-Null']))->mustRun();
        }
        clearstatcache(true, $link);
        $this->createdLinks[] = $link;
        $this->assertTrue($this->isDirectoryLink($link), 'O ensaio exige uma ligação de pastas real.');
    }

    private function fixture(callable $check): void
    {
        $oldRoot = base_path();
        $root = $oldRoot.'/tests/.runtime/vendor-links-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($root.'/vendor/composer');
        File::ensureDirectoryExists($root.'/vendor/tests');
        File::ensureDirectoryExists($root.'/packages/plugin/assets');
        File::put($root.'/packages/plugin/composer.json', '{"name":"tests/plugin"}');
        File::put($root.'/packages/plugin/assets/marker.txt', 'original-content');
        File::put($root.'/vendor/composer/installed.json', json_encode(['packages' => [['name' => 'tests/plugin', 'version' => '1.0.0']]]));
        $this->app->setBasePath($root);
        $this->createdLinks = [];
        try { $check($root); }
        finally {
            $this->app->setBasePath($oldRoot);
            foreach (array_reverse($this->createdLinks) as $link) {
                $this->assertStringStartsWith($root.'/', $link);
                if (PHP_OS_FAMILY === 'Windows' && ! is_link($link)) @rmdir($link);
                else @unlink($link);
            }
            File::deleteDirectory($root);
        }
    }

    private function copy(string $root): void
    {
        (new \ReflectionMethod(ComposerDeploymentTransaction::class, 'copyVendor'))->invoke(new ComposerDeploymentTransaction, $root.'/vendor', $root.'/prepared/vendor', true);
    }

    public function test_registered_package_link_inside_application_is_materialized_without_mutating_original(): void
    {
        $this->fixture(function ($root): void {
            $this->directoryLink($root.'/packages/plugin', $root.'/vendor/tests/plugin');
            $this->copy($root);
            $this->assertFalse($this->isDirectoryLink($root.'/prepared/vendor/tests/plugin'));
            $this->assertSame('original-content', File::get($root.'/prepared/vendor/tests/plugin/assets/marker.txt'));
            File::put($root.'/prepared/vendor/tests/plugin/assets/marker.txt', 'prepared-only');
            $this->assertSame('original-content', File::get($root.'/packages/plugin/assets/marker.txt'));
            $this->assertTrue($this->isDirectoryLink($root.'/vendor/tests/plugin'));
        });
    }

    public function test_nested_directory_link_still_requires_a_separate_publication(): void
    {
        $this->fixture(function ($root): void {
            File::moveDirectory($root.'/packages/plugin', $root.'/vendor/tests/plugin');
            $this->directoryLink($root.'/vendor/tests/plugin/assets', $root.'/vendor/tests/plugin/asset-alias');
            try { $this->copy($root); $this->fail('Uma ligação de pastas interna não declarada deve ser recusada.'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('vendor/tests/plugin/asset-alias', $e->getMessage()); }
            $this->assertSame('original-content', File::get($root.'/vendor/tests/plugin/assets/marker.txt'));
        });
    }

    public function test_link_outside_application_is_refused_without_disclosing_target(): void
    {
        $this->fixture(function ($root): void {
            $outside = dirname($root).'/outside-'.bin2hex(random_bytes(5));
            File::ensureDirectoryExists($outside);
            File::put($outside.'/composer.json', '{"name":"tests/plugin"}');
            try {
                $this->directoryLink($outside, $root.'/vendor/tests/plugin');
                try { $this->copy($root); $this->fail('Uma origem externa não pode ser copiada.'); }
                catch (\RuntimeException $e) { $this->assertStringContainsString('vendor/tests/plugin', $e->getMessage()); $this->assertStringNotContainsString($outside, $e->getMessage()); }
                $this->assertFileDoesNotExist($root.'/prepared/vendor/tests/plugin/composer.json');
            } finally { File::deleteDirectory($outside); }
        });
    }

    public function test_unregistered_package_link_and_mismatched_identity_are_refused(): void
    {
        foreach (['unregistered', 'mismatched'] as $case) $this->fixture(function ($root) use ($case): void {
            if ($case === 'unregistered') File::put($root.'/vendor/composer/installed.json', '{"packages":[]}');
            else File::put($root.'/packages/plugin/composer.json', '{"name":"tests/other"}');
            $this->directoryLink($root.'/packages/plugin', $root.'/vendor/tests/plugin');
            try { $this->copy($root); $this->fail('A identidade do pacote tem de ser confirmada.'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('vendor/tests/plugin', $e->getMessage()); }
        });
    }

    public function test_nested_link_cannot_escape_package_or_create_a_cycle(): void
    {
        foreach (['escape', 'cycle'] as $case) $this->fixture(function ($root) use ($case): void {
            File::moveDirectory($root.'/packages/plugin', $root.'/vendor/tests/plugin');
            $this->directoryLink($case === 'cycle' ? $root.'/vendor/tests/plugin' : $root.'/packages', $root.'/vendor/tests/plugin/alias');
            try { $this->copy($root); $this->fail('A ligação insegura não pode ser copiada.'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('vendor/tests/plugin/alias', $e->getMessage()); }
            $this->assertSame('original-content', File::get($root.'/vendor/tests/plugin/assets/marker.txt'));
        });
    }
}
