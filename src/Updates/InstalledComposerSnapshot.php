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
            unset($package['install-path'], $package['installation-source'], $package['version_normalized'], $package['dev_requirement']);
            if ($name !== $removing && (($package['dist']['type'] ?? null) === 'path' || str_starts_with($package['dist']['url'] ?? '', 'storage/app/composer-snapshots/'))) {
                $package['extra']['cms-original-dist-type'] = 'path';
                $reference = $package['dist']['reference'] ?? null;
                $package['dist'] = ['type'=>'zip', 'url'=>$this->archive($name, $stage), 'reference'=>$reference];
                unset($package['source']);
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

    private function archive(string $name, string $stage): string
    {
        $vendor = realpath(base_path('vendor'));
        $source = realpath(base_path('vendor/'.$name));
        if ($vendor === false || $source === false || !str_starts_with(str_replace('\\', '/', $source).'/', str_replace('\\', '/', $vendor).'/')) throw new RuntimeException('Não foi possível encontrar os ficheiros instalados de '.$name.'.');
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
}
