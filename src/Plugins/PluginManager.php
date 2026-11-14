<?php

namespace Pcteckserv\CmsCore\Plugins;

use Composer\InstalledVersions;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\Models\InstalledPlugin;

class PluginManager
{
    public function __construct(
        private readonly PluginCatalog $catalog,
        private readonly ?InstalledPluginVersionReader $installedVersionReader = null,
    ) {
    }

    /**
     * @return Collection<int, InstalledPlugin>
     */
    public function sync(): Collection
    {
        return $this->catalog->all()
            ->map(function ($definition): InstalledPlugin {
                $plugin = InstalledPlugin::query()->firstOrNew(['slug' => $definition->slug]);
                $isInstalled = $this->isComposerInstalled($definition->package);
                $metadata = $plugin->metadata ?? [];
                $plugin->package = $definition->package;
                $isPath = $isInstalled && app(InstalledPluginSource::class)->isPath($plugin);
                if ($isPath) {
                    $metadata['repository_type'] = 'path';
                }
                $manifestVersion = $isPath
                    ? ($this->installedVersionReader ?? new InstalledPluginVersionReader())->read($definition->package)
                    : null;

                if ($manifestVersion !== null) {
                    $metadata['version'] = $manifestVersion;
                    $metadata['last_applied_release'] = $manifestVersion;
                }

                $plugin->fill([
                    'name' => $definition->name,
                    'package' => $definition->package,
                    'label' => $definition->label,
                    'description' => $definition->description,
                    'provider' => $definition->provider,
                    'installed_version' => $isInstalled
                        ? ($manifestVersion ?? $metadata['version'] ?? $this->installedVersion($definition->package))
                        : null,
                    'metadata' => $metadata,
                ]);

                if ($isInstalled && $plugin->installed_at === null) {
                    $plugin->installed_at = now();
                }

                if (! $isInstalled) {
                    $plugin->installed_at = null;
                }

                $plugin->save();

                return $plugin;
            })
            ->values();
    }

    /**
     * @return Collection<int, InstalledPlugin>
     */
    public function all(): Collection
    {
        $this->sync();

        return InstalledPlugin::query()
            ->orderBy('label')
            ->get();
    }

    public function enable(string $slug): InstalledPlugin
    {
        $plugin = $this->findOrFail($slug);

        if ($plugin->installed_version === null) {
            throw ValidationException::withMessages([
                'plugin' => 'O plugin tem de estar instalado antes de ser ativado.',
            ]);
        }

        foreach (config('cms-plugins.plugins.'.$slug.'.requires', []) as $dependency) {
            if (! $this->isEnabled($dependency)) {
                throw ValidationException::withMessages(['plugin' => 'Ative primeiro o plugin '.(config('cms-plugins.plugins.'.$dependency.'.label') ?: $dependency).'.']);
            }
        }

        $plugin->forceFill([
            'status' => 'enabled',
            'enabled_at' => now(),
            'disabled_at' => null,
            'last_error' => null,
        ])->save();

        return $plugin;
    }

    public function disable(string $slug): InstalledPlugin
    {
        $plugin = $this->findOrFail($slug);

        foreach (config('cms-plugins.plugins', []) as $dependent => $definition) {
            if (in_array($slug, $definition['requires'] ?? [], true) && InstalledPlugin::where('slug', $dependent)->where('status', 'enabled')->exists()) {
                throw ValidationException::withMessages(['plugin' => 'Desative primeiro o plugin '.($definition['label'] ?? $dependent).'.']);
            }
        }

        $plugin->forceFill([
            'status' => 'disabled',
            'disabled_at' => now(),
        ])->save();

        return $plugin;
    }

    public function isEnabled(string $slug): bool
    {
        if (! config('cms-plugins.enabled', true)) {
            return false;
        }

        return InstalledPlugin::query()
            ->where('slug', $slug)
            ->where('status', 'enabled')
            ->exists();
    }

    private function findOrFail(string $slug): InstalledPlugin
    {
        $this->sync();

        return InstalledPlugin::query()->where('slug', $slug)->firstOrFail();
    }

    private function isComposerInstalled(string $package): bool
    {
        return InstalledVersions::isInstalled($package);
    }

    private function installedVersion(string $package): ?string
    {
        if (! $this->isComposerInstalled($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package) ?: InstalledVersions::getVersion($package);
    }
}
