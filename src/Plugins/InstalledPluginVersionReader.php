<?php

namespace Pcteckserv\CmsCore\Plugins;

class InstalledPluginVersionReader
{
    public function read(string $package): ?string
    {
        if (! preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/', $package)) {
            return null;
        }

        $manifestPath = base_path('vendor/'.$package.'/cms-plugin.json');

        if (! is_file($manifestPath) || ! is_readable($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $version = is_array($manifest) ? ($manifest['version'] ?? null) : null;

        return is_string($version)
            && preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version)
                ? $version
                : null;
    }
}
