<?php

namespace Pcteckserv\CmsCore\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Models\SiteOption;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class Website
{
    public const POLICIES = ['privacy' => 'Política de privacidade', 'cookies' => 'Política de cookies', 'terms' => 'Termos e condições', 'refunds' => 'Política de reembolso'];

    public const SLUGS = ['privacy' => 'politica-de-privacidade', 'cookies' => 'politica-de-cookies', 'terms' => 'termos-e-condicoes', 'refunds' => 'politica-de-reembolso'];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('cms_site_options')) {
            return $default;
        }
        $value = SiteOption::where('key', 'website.'.$key)->value('value');

        return $value === null ? $default : json_decode($value, true);
    }

    public function save(string $key, mixed $value): void
    {
        SiteOption::updateOrCreate(['key' => 'website.'.$key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    private function legacy(string $key, mixed $default): mixed
    {
        if (Schema::hasTable('cms_shop_settings')) {
            $settings = json_decode(DB::table('cms_shop_settings')->where('id', 1)->value('settings') ?? '{}', true);

            return $settings[$key] ?? config('cms-ecommerce.'.$key, $default);
        }

        return $default;
    }

    public function registration(): bool
    {
        return (bool) $this->get('registration', $this->legacy('registration_enabled', config('cms-core.customers.registration', true)));
    }

    public function requireAccount(): bool
    {
        return (bool) $this->get('require_account', $this->legacy('checkout_require_account', true));
    }

    public function policy(string $key): string
    {
        return (string) $this->get('policy.'.$key, $key === 'terms' ? $this->legacy('terms', '') : '');
    }

    public function commerce(): bool
    {
        return Schema::hasTable('cms_plugins') && app(PluginManager::class)->isEnabled('ecommerce');
    }

    public function missingPolicies(): array
    {
        $keys = $this->commerce() ? array_keys(self::POLICIES) : ['privacy', 'cookies'];

        return array_filter(self::POLICIES, fn ($label, $key) => in_array($key, $keys, true) && trim($this->policy($key)) === '', ARRAY_FILTER_USE_BOTH);
    }

    public function menus(): array
    {
        return $this->get('menus', []);
    }

    public function menu(string $slug): ?array
    {
        return collect($this->menus())->firstWhere('slug', $slug);
    }

    public function menuTree(array $menu, string $parent = ''): array
    {
        return collect($menu['items'])->filter(fn ($item) => (string) ($item['parent'] ?? '') === $parent)->sortBy('position')->map(function ($item) use ($menu) {
            $item['children'] = $this->menuTree($menu, $item['id']);

            return $item;
        })->values()->all();
    }
}
