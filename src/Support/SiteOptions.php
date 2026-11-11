<?php

namespace Pcteckserv\CmsCore\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Models\SiteOption;
use Throwable;

class SiteOptions
{
    private const CACHE_KEY = 'cms-core.site-options';

    public function all(): array
    {
        $options = $this->rawOptions();
        $urls = app(SiteUrl::class);
        $bases = $this->urlBases($options);
        foreach (['site_icon_url', 'seo_default_og_image', 'seo_organization_logo', 'seo_robots_sitemap_url'] as $key) {
            if (is_string($options[$key] ?? null) && $options[$key] !== '') {
                $options[$key] = $urls->absolute($options[$key], $bases);
            }
        }
        $options['site_url'] = $options['seo_base_url'] = $urls->base();

        return $options;
    }

    public function storedUrlBases(): array
    {
        return $this->urlBases($this->rawOptions());
    }

    private function urlBases(array $options): array
    {
        return array_values(array_unique(array_filter([
            app(SiteUrl::class)->base(), $options['site_url'] ?? null, $options['seo_base_url'] ?? null,
        ], 'is_string')));
    }

    private function rawOptions(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->loadAll());
        } catch (Throwable) {
            return $this->loadAll();
        }
    }

    public function loadAll(): array
    {
        $defaults = $this->defaults();

        try {
            if (! Schema::hasTable('cms_site_options')) {
                return $defaults;
            }
        } catch (Throwable) {
            return $defaults;
        }

        $stored = SiteOption::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        return array_replace($defaults, array_filter(
            $stored,
            static fn ($value): bool => $value !== null && $value !== ''
        ));
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->all()[$key] ?? $fallback;
    }

    public function siteIconUrl(): string
    {
        $url = $this->get('site_icon_url');

        if (is_string($url) && trim($url) !== '') {
            return $this->isDefaultCoreFavicon($url) ? $this->versionedCoreFaviconUrl() : $url;
        }

        $default = $this->defaults()['site_icon_url'] ?? null;

        $defaultUrl = is_string($default) && trim($default) !== ''
            ? $default
            : '/vendor/cms-core/images/favicon.png';

        return $this->isDefaultCoreFavicon($defaultUrl) ? $this->versionedCoreFaviconUrl() : $defaultUrl;
    }

    public function setMany(array $options): void
    {
        // Preservar os endereços legados para reconhecer referências internas após uma migração.
        unset($options['site_url'], $options['seo_base_url']);
        $bases = $this->storedUrlBases();
        foreach ($options as $key => $value) {
            if (in_array($key, ['site_icon_url', 'seo_default_og_image', 'seo_organization_logo', 'seo_robots_sitemap_url'], true) && is_string($value)) {
                $value = app(SiteUrl::class)->relative($value, $bases);
            }
            SiteOption::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // A cache pode ainda não estar disponível durante a instalação.
        }
    }

    public function applyMailConfig(): void
    {
        $options = $this->all();

        if (! $this->truthy($options['smtp_enabled'] ?? false)) {
            return;
        }

        $encryption = $options['smtp_encryption'] ?: null;

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.host' => $options['smtp_host'],
            'mail.mailers.smtp.port' => (int) $options['smtp_port'],
            'mail.mailers.smtp.username' => $options['smtp_username'] ?: null,
            'mail.mailers.smtp.password' => $options['smtp_password'] ?: null,
            'mail.from.address' => $options['smtp_from_address'],
            'mail.from.name' => $options['smtp_from_name'],
        ]);
    }

    public function defaults(): array
    {
        return config('cms-core.site_options', []);
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function isDefaultCoreFavicon(string $url): bool
    {
        $url = app(SiteUrl::class)->relative($url);

        return strtok($url, '?') === '/vendor/cms-core/images/favicon.png'
            || strtok($url, '?') === '/favicon.ico';
    }

    private function versionedCoreFaviconUrl(): string
    {
        $path = __DIR__.'/../../resources/images/favicon.png';

        $prefix = rtrim((string) parse_url(app(SiteUrl::class)->base(), PHP_URL_PATH), '/');

        return $prefix.'/favicon.ico?v='.(is_file($path) ? filemtime($path) : 'default');
    }
}
