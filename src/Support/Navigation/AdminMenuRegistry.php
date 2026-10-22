<?php

namespace Pcteckserv\CmsCore\Support\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class AdminMenuRegistry
{
    /** @var array<string, array{label: string, route: string, permission: string, plugin: string, active: string, order: int, parent: ?string}> */
    private array $items = [];

    public function register(
        string $key,
        string $label,
        string $route,
        string $permission,
        string $plugin,
        string $active,
        int $order = 100,
        ?string $parent = null,
    ): void {
        $this->items[$key] = compact('label', 'route', 'permission', 'plugin', 'active', 'order', 'parent');
    }

    public function visible(): Collection
    {
        $states = [];
        $plugins = app(PluginManager::class);

        $enabled = collect($this->items)->filter(function (array $item) use ($plugins, &$states): bool {
            if (! Route::has($item['route'])) {
                return false;
            }

            $states[$item['plugin']] ??= $plugins->isEnabled($item['plugin']);

            return $states[$item['plugin']];
        })->sortBy('order');
        $result = collect();
        foreach ($enabled as $key => $item) {
            if ($item['parent'] !== null) {
                continue;
            }
            $children = $enabled->filter(fn ($child) => $child['parent'] === $key && Gate::allows($child['permission']))->values();
            if (Gate::allows($item['permission']) || $children->isNotEmpty()) {
                $item['children'] = $children->all();
                $item['key'] = $key;
                $result->push($item);
            }
        }

        return $result;
    }
}
