<?php

namespace Pcteckserv\CmsCore\Updates;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/** Usa os metadados e o código instalados, sem consultar pastas de desenvolvimento. */
class InstalledComposerSnapshot
{
    public function portableManifest(array $manifest): array
    {
        $repositories = $manifest['repositories'] ?? [];
        $filtered = array_filter($repositories, function ($repository) {
            if (!is_array($repository) || ($repository['type'] ?? null) !== 'path') return true;
            $url = $repository['url'] ?? '';
            if (!is_string($url) || trim($url) === '') return false;
            $absolute = str_starts_with($url, '/') || str_starts_with($url, '\\') || preg_match('~^[a-zA-Z]:[\\\\/]~', $url);
            return is_dir($absolute ? $url : base_path($url));
        });
        $manifest['repositories'] = array_is_list($repositories) ? array_values($filtered) : $filtered;
        return $manifest;
    }

    public function baseline(array $lock, array $installed, string $stage, ?string $removing = null): array
    {
        $devNames = $installed['dev-package-names'] ?? array_column($lock['packages-dev'] ?? [], 'name');
        $lock['packages'] = $lock['packages-dev'] = [];
        foreach ($installed['packages'] ?? $installed as $package) {
            $name = $package['name'] ?? '';
            if (!preg_match('~^[a-z0-9_.-]+/[a-z0-9_.-]+$~D', $name) || !is_string($package['version'] ?? null)) throw new RuntimeException('Os metadados Composer instalados estão incompletos.');
            $installPath = $package['install-path'] ?? null;
            unset($package['install-path'], $package['installation-source'], $package['version_normalized'], $package['dev_requirement']);
            if ($name !== $removing && (($package['dist']['type'] ?? null) === 'path' || str_starts_with($package['dist']['url'] ?? '', 'storage/app/composer-snapshots/'))) {
                $package['extra']['cms-original-dist-type'] = 'path';
                $reference = $package['dist']['reference'] ?? null;
                $package['dist'] = ['type'=>'zip', 'url'=>$this->archive($name, $stage, $installPath), 'reference'=>$reference];
                // Opções de transporte de path (relative/symlink) não são opções HTTP de um ZIP.
                unset($package['source'], $package['transport-options']);
            }
            $lock[in_array($name, $devNames, true) ? 'packages-dev' : 'packages'][] = $package;
        }
        // Os aliases antigos podem apontar para versões que já não estão instaladas.
        $versions = array_column([...$lock['packages'], ...$lock['packages-dev']], 'version', 'name');
        $lock['aliases'] = array_values(array_filter($lock['aliases'] ?? [], fn ($alias) => isset($versions[$alias['package'] ?? '']) && ltrim($versions[$alias['package']], 'v') === ltrim($alias['version'] ?? '', 'v')));
        return $lock;
    }

    public function publishArchives(string $stage): void
    {
        $relative = 'storage/app/composer-snapshots';
        if (!is_dir($stage.'/'.$relative)) return;
        File::ensureDirectoryExists(base_path($relative), 0700);
        foreach (glob($stage.'/'.$relative.'/*.zip') ?: [] as $file) {
            $target = base_path($relative.'/'.basename($file));
            if (is_file($target) && hash_file('sha256', $target) === hash_file('sha256', $file)) continue;
            if (!File::copy($file, $target)) throw new RuntimeException('Não foi possível preservar a origem dos plugins instalados.');
        }
    }

    private function archive(string $name, string $stage, mixed $installPath): string
    {
        $source = $this->installedDirectory($name, $installPath);
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('A extensão PHP ZIP é necessária para preservar os plugins distribuídos com o site.');
        $relative = 'storage/app/composer-snapshots';
        File::ensureDirectoryExists($stage.'/'.$relative, 0700);
        $temporary = $stage.'/'.$relative.'/snapshot-'.bin2hex(random_bytes(6)).'.zip';
        $zip = new ZipArchive();
        if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Não foi possível preparar o código instalado de '.$name.'.');
        try {
            $files = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), function ($file) use ($name) {
                if ($file->isLink()) throw new RuntimeException('O pacote '.$name.' contém ligações simbólicas e exige uma publicação preparada. Os ficheiros instalados foram preservados.');
                return $file->getFilename() !== '.git';
            }));
            foreach ($files as $file) {
                if (!$file->isFile()) continue;
                $path = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
                if (!$zip->addFile($file->getPathname(), 'package/'.$path)) throw new RuntimeException('Não foi possível preservar um ficheiro de '.$name.'.');
            }
        } finally { $zip->close(); }
        $name = str_replace('/', '-', $name).'-'.hash_file('sha256', $temporary).'.zip';
        if (!rename($temporary, $stage.'/'.$relative.'/'.$name)) throw new RuntimeException('Não foi possível finalizar a origem instalada.');
        return $relative.'/'.$name;
    }

    private function installedDirectory(string $name, mixed $installPath): string
    {
        $root = realpath(base_path());
        if ($root === false) throw new RuntimeException('Não foi possível confirmar a pasta da aplicação.');
        if ($installPath !== null && (!is_string($installPath) || str_contains($installPath, "\0"))) {
            throw new RuntimeException('Os metadados de localização de '.$name.' são inválidos.');
        }

        // installed.json usa caminhos relativos a vendor/composer, não a vendor.
        if (is_string($installPath) && $installPath !== '') {
            $absolute = str_starts_with($installPath, '/') || str_starts_with($installPath, '\\') || preg_match('~^[a-zA-Z]:[\\\\/]~', $installPath);
            $registered = realpath($absolute ? $installPath : base_path('vendor/composer/'.$installPath));
            if ($registered !== false) {
                $this->assertInsideApplication($registered, $root, $name);
                if (is_file($registered.'/composer.json')) {
                    $this->assertPackageDirectory($registered, $root, $name);
                    return $registered;
                }
            }
        }

        // Distribuições antigas podem copiar os plugins para packages e conservar
        // o install-path original. Não consultar dist.url: é uma origem de desenvolvimento.
        $candidates = [];
        foreach (['vendor/'.$name, 'packages/'.$name] as $relative) {
            $directory = realpath(base_path($relative));
            if ($directory !== false && is_dir($directory) && is_file($directory.'/composer.json')) $candidates[$this->normalizedPath($directory)] = $directory;
        }
        if (count($candidates) > 1) {
            throw new RuntimeException('Existe mais de uma cópia instalada de '.$name.'. Confirme a localização Composer antes de atualizar.');
        }
        if ($candidates === []) {
            throw new RuntimeException('Não foi possível encontrar os ficheiros instalados de '.$name.' em vendor, packages ou na localização registada pelo Composer.');
        }
        $directory = reset($candidates);
        $this->assertPackageDirectory($directory, $root, $name);
        return $directory;
    }

    private function assertPackageDirectory(string $directory, string $root, string $name): void
    {
        $this->assertInsideApplication($directory, $root, $name);
        $manifest = $directory.'/composer.json';
        $composer = is_file($manifest) && is_readable($manifest) ? json_decode((string) file_get_contents($manifest), true) : null;
        if (!is_array($composer) || ($composer['name'] ?? null) !== $name) {
            throw new RuntimeException('Não foi possível confirmar a identidade dos ficheiros instalados de '.$name.'.');
        }
    }

    private function assertInsideApplication(string $directory, string $root, string $name): void
    {
        if (!str_starts_with($this->normalizedPath($directory).'/', $this->normalizedPath($root).'/')) {
            throw new RuntimeException('Os ficheiros instalados de '.$name.' estão fora da aplicação. É necessária uma distribuição própria.');
        }
    }

    private function normalizedPath(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    }
}
