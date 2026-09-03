<?php

namespace Pcteckserv\CmsCore\Plugins;

use Illuminate\Support\Collection;
use Pcteckserv\CmsCore\Plugins\DTOs\PluginDefinition;

class PluginCatalog
{
    /**
     * @return Collection<string, PluginDefinition>
     */
    public function all(): Collection
    {
        return collect(config('cms-plugins.plugins', []))
            ->filter(fn (mixed $definition, mixed $slug): bool => is_string($slug) && is_array($definition))
            ->mapWithKeys(function (array $definition, string $slug): array {
                $package = $definition['package'] ?? $definition['name'] ?? null;

                if (! is_string($package) || $package === '') {
                    return [];
                }

                return [$slug => new PluginDefinition(
                    slug: $slug,
                    name: (string) ($definition['name'] ?? $package),
                    package: $package,
                    label: (string) ($definition['label'] ?? $slug),
                    description: isset($definition['description']) ? (string) $definition['description'] : null,
                    provider: isset($definition['provider']) ? (string) $definition['provider'] : null,
                    repository: isset($definition['repository']) ? (string) $definition['repository'] : null,
                )];
            });
    }

    public function find(string $slug): ?PluginDefinition
    {
        return $this->all()->get($slug);
    }

    /**
     * @return array<int, string>
     */
    public function packages(): array
    {
        return $this->all()
            ->pluck('package')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
