<?php

namespace Pcteckserv\CmsCore\Support;

use RuntimeException;

class ComposerRepositoryCleaner
{
    public function preferRemotePackage(string $package, string $url): void
    {
        $path = base_path('composer.json');
        $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $repositories = $manifest['repositories'] ?? [];

        foreach ($repositories as &$repository) {
            if (is_array($repository) && ($repository['type'] ?? null) === 'path') {
                $repository['exclude'] = array_values(array_unique([
                    ...(array) ($repository['exclude'] ?? []), $package,
                ]));
            }
        }
        unset($repository);

        $remote = ['type' => 'vcs', 'url' => $url, 'only' => [$package]];
        $manifest['repositories'] = array_is_list($repositories)
            ? [$remote, ...$repositories]
            : ['cms-core-updates' => $remote, ...array_diff_key($repositories, ['cms-core-updates' => true])];
        $temporaryPath = $path.'.tmp-'.bin2hex(random_bytes(6));
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;

        if (file_put_contents($temporaryPath, $encoded, LOCK_EX) === false || ! rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Não foi possível configurar o repositório remoto do Core no composer.json. Verifique as permissões de escrita.');
        }
    }

    public function removeInvalidPathRepositories(bool $recoverInstalledPackages = false): void
    {
        $manifestPath = base_path('composer.json');

        if (! is_file($manifestPath) || ! is_readable($manifestPath)) {
            throw new RuntimeException('O ficheiro composer.json não existe ou não pode ser lido.');
        }

        $contents = file_get_contents($manifestPath);
        $manifest = json_decode($contents ?: '', true);

        if (! is_array($manifest)) {
            throw new RuntimeException('O ficheiro composer.json não contém JSON válido.');
        }

        $repositories = $manifest['repositories'] ?? [];

        if (! is_array($repositories)) {
            return;
        }

        $isList = array_is_list($repositories);
        $filtered = array_filter(
            $repositories,
            fn ($repository, $name) => ! str_starts_with((string) $name, 'cms-recovered-')
                && (! is_array($repository) || ! str_starts_with((string) ($repository['name'] ?? ''), 'cms-recovered-'))
                && ! $this->isInvalidPathRepository($repository),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($recoverInstalledPackages) {
            $filtered = $this->recoverInstalledPathPackages($filtered);
        }

        if ($filtered === $repositories) {
            return;
        }

        $manifest['repositories'] = $isList ? array_values($filtered) : $filtered;
        $encoded = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
        $temporaryPath = $manifestPath.'.tmp-'.bin2hex(random_bytes(6));

        if (file_put_contents($temporaryPath, $encoded, LOCK_EX) === false || ! rename($temporaryPath, $manifestPath)) {
            @unlink($temporaryPath);

            throw new RuntimeException('Não foi possível remover repositórios locais inválidos do composer.json.');
        }
    }

    /**
     * @param  array<int|string, mixed>  $repositories
     * @return array<int|string, mixed>
     */
    private function recoverInstalledPathPackages(array $repositories): array
    {
        $lockPath = base_path('composer.lock');
        $lock = is_file($lockPath) ? json_decode(file_get_contents($lockPath) ?: '', true) : null;

        if (! is_array($lock)) {
            return $repositories;
        }

        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            $name = $package['name'] ?? null;

            if (! is_string($name) || ($package['dist']['type'] ?? null) !== 'path') {
                continue;
            }

            $relativePath = 'packages/'.$name;

            if (! is_dir(base_path($relativePath)) || $this->hasRepositoryFor($repositories, $relativePath)
                || $this->hasExplicitRemoteRepository($repositories, $name)) {
                continue;
            }

            $options = ['symlink' => false];
            $version = $package['version'] ?? null;

            // A cópia de deploy pode não declarar a versão que ficou registada no lock.
            if (is_string($version) && $version !== '') {
                $options['versions'] = [$name => $version];
            }

            $repositories['cms-recovered-'.sha1($name)] = [
                'name' => 'cms-recovered-'.sha1($name),
                'type' => 'path',
                'url' => $relativePath,
                'options' => $options,
            ];
        }

        return $repositories;
    }

    private function hasExplicitRemoteRepository(array $repositories, string $package): bool
    {
        foreach ($repositories as $repository) {
            if (is_array($repository) && in_array($repository['type'] ?? null, ['vcs', 'git', 'github'], true)
                && in_array($package, (array) ($repository['only'] ?? []), true)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int|string, mixed> $repositories */
    private function hasRepositoryFor(array $repositories, string $relativePath): bool
    {
        $expected = realpath(base_path($relativePath));

        foreach ($repositories as $repository) {
            if (! is_array($repository) || ($repository['type'] ?? null) !== 'path') {
                continue;
            }

            $url = $repository['url'] ?? null;
            $path = is_string($url) && $url !== ''
                ? realpath($this->isAbsolutePath($url) ? $url : base_path($url))
                : false;

            if ($path !== false && $path === $expected) {
                return true;
            }
        }

        return false;
    }

    private function isInvalidPathRepository(mixed $repository): bool
    {
        if (! is_array($repository) || ($repository['type'] ?? null) !== 'path') {
            return false;
        }

        $url = $repository['url'] ?? null;

        if (! is_string($url) || trim($url) === '') {
            return true;
        }

        $path = $this->isAbsolutePath($url) ? $url : base_path($url);

        return ! is_dir($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
