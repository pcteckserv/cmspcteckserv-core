<?php

namespace Pcteckserv\CmsCore\Updates;

use Pcteckserv\CmsCore\Support\ComposerCommand;

class ComposerInstalledPackageReader
{
    /** Confirma ausência nos três inventários, sem executar Composer nem apagar ficheiros. */
    public function isAbsent(string $package): bool
    {
        if (! preg_match('~^[a-z0-9_.-]+/[a-z0-9_.-]+$~D', $package)) return false;
        foreach (['vendor/composer/installed.json', 'composer.lock', 'composer.json'] as $file) {
            $path = base_path($file);
            if (! is_file($path) || ! is_readable($path)) return false;
            $data = json_decode((string) file_get_contents($path), true);
            if (! is_array($data)) return false;
            if ($file === 'composer.json') {
                if (! is_string($data['name'] ?? null) || ! is_array($data['require'] ?? []) || ! is_array($data['require-dev'] ?? [])) return false;
                if (array_key_exists($package, $data['require'] ?? []) || array_key_exists($package, $data['require-dev'] ?? [])) return false;
            } else {
                if ($file === 'composer.lock' && (! is_array($data['packages'] ?? null) || ! is_array($data['packages-dev'] ?? []))) return false;
                if ($file !== 'composer.lock' && isset($data['packages']) && ! is_array($data['packages'])) return false;
                $packages = $file === 'composer.lock' ? [...($data['packages'] ?? []), ...($data['packages-dev'] ?? [])] : ($data['packages'] ?? $data);
                if (! array_is_list($packages)) return false;
                foreach ($packages as $item) if (! is_array($item) || ! is_string($item['name'] ?? null) || $item['name'] === $package) return false;
            }
        }
        // Ficheiros antigos podem continuar disponíveis sem uma instalação Composer.
        // A reconciliação só altera o inventário; esses ficheiros não são apagados.
        return ! \Composer\InstalledVersions::isInstalled($package);
    }

    public function __construct(?ComposerCommand $composerCommand = null)
    {
        // Mantém compatibilidade com os consumidores que injetam o comando Composer.
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $package): array
    {
        $path = base_path('vendor/composer/installed.json');
        clearstatcache(true, $path);

        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $installed = json_decode((string) file_get_contents($path), true);

        if (! is_array($installed)) {
            return [];
        }

        // Ler novamente após cada atualização, sem usar o cache de InstalledVersions.
        foreach (($installed['packages'] ?? $installed) as $packageData) {
            if (is_array($packageData) && ($packageData['name'] ?? null) === $package) {
                return $packageData;
            }
        }

        return [];
    }
}
