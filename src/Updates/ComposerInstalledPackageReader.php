<?php

namespace Pcteckserv\CmsCore\Updates;

use Pcteckserv\CmsCore\Support\ComposerCommand;

class ComposerInstalledPackageReader
{
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
