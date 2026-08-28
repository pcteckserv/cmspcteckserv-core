<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Seo\Models\SeoRedirect;
use Throwable;

class RedirectResolver
{
    public const CACHE_KEY = 'cms-core.seo.redirects';

    public function resolve(string $path): ?SeoRedirect
    {
        $source = '/'.ltrim($path, '/');

        try {
            if (! Schema::hasTable('seo_redirects')) {
                return null;
            }

            $items = Cache::rememberForever(self::CACHE_KEY, fn (): array => SeoRedirect::query()
                ->where('is_active', true)
                ->get()
                ->keyBy('source')
                ->all());
        } catch (Throwable) {
            return null;
        }

        return $items[$source] ?? null;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
