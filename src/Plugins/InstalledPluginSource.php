<?php

namespace Pcteckserv\CmsCore\Plugins;

use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Updates\ComposerInstalledPackageReader;

class InstalledPluginSource
{
    public function __construct(private readonly ComposerInstalledPackageReader $packages)
    {
    }

    public function isPath(InstalledPlugin $plugin, ?array $installedPackage = null): bool
    {
        $type = $plugin->metadata['repository_type'] ?? null;
        if (is_string($type) && $type !== '') {
            return $type === 'path';
        }

        // Instalações antigas podem não ter guardado os metadados da origem.
        $installedPackage ??= $this->packages->read($plugin->package);

        return ($installedPackage['dist']['type'] ?? null) === 'path';
    }
}
