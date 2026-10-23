<?php

namespace Pcteckserv\CmsCore\Customers;

use Closure;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class CustomerModules
{
    private array $modules = [];

    public function register(string $plugin, string $label, string $permission, Closure $history, Closure $records): void
    {
        $this->modules[$plugin] = compact('label', 'permission', 'history', 'records');
    }

    public function hasHistory(Customer $customer): bool
    {
        // History protects deletion even while its owning plugin is disabled.
        foreach ($this->modules as $module) {
            if (($module['history'])($customer)) {
                return true;
            }
        }

        return false;
    }

    public function sections(Customer $customer, bool $admin = true): array
    {
        $result = [];
        foreach ($this->modules as $plugin => $module) {
            if (! app(PluginManager::class)->isEnabled($plugin)) {
                continue;
            }
            if ($admin && ! auth('web')->user()?->can($module['permission'])) {
                continue;
            }
            $result[] = ['key' => $plugin, 'label' => $module['label'], 'records' => ($module['records'])($customer, $admin)];
        }

        return $result;
    }
}
