<?php

namespace Pcteckserv\CmsCore\Support\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class AdminMenuRegistry
{
    /** @var array<string, array{label: string, route: string, permission: string, plugin: string, active: string, order: int}> */
    private array $items = [];

    public function register(
        string $key,
        string $label,
        string $route,
        string $permission,
        string $plugin,
        string $active,
        int $order = 100,
    ): void {
        $this->items[$key] = compact('label', 'route', 'permission', 'plugin', 'active', 'order');
    }

    public function visible(): Collection
    {
        $states = [];
        $plugins = app(PluginManager::class);

        return collect($this->items)->filter(function (array $item) use ($plugins, &$states): bool {
            if (! Route::has($item['route']) || ! Gate::allows($item['permission'])) {
                return false;
            }

            $states[$item['plugin']] ??= $plugins->isEnabled($item['plugin']);

            return $states[$item['plugin']];
        })->sortBy('order')->values();
    }
}
