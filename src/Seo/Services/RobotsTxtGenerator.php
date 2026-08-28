<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Support\Facades\Cache;
use Pcteckserv\CmsCore\Support\SiteOptions;

class RobotsTxtGenerator
{
    public const CACHE_KEY = 'cms-core.seo.robots';

    public function __construct(private readonly SiteOptions $siteOptions)
    {
    }

    public function text(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): string => $this->buildText());
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function buildText(): string
    {
        $advanced = trim((string) $this->siteOptions->get('seo_robots_advanced', ''));

        if ($advanced !== '') {
            return $advanced."\n";
        }

        $lines = ['User-agent: *'];
        $allow = array_filter(array_map('trim', explode("\n", (string) $this->siteOptions->get('seo_robots_allow', ''))));
        $disallow = array_filter(array_map('trim', explode("\n", (string) $this->siteOptions->get('seo_robots_disallow', ''))));

        foreach ($allow as $path) {
            $lines[] = 'Allow: '.$path;
        }

        foreach ($disallow as $path) {
            if ($path !== '/') {
                $lines[] = 'Disallow: '.$path;
            }
        }

        $sitemap = $this->siteOptions->get('seo_robots_sitemap_url') ?: rtrim((string) $this->siteOptions->get('seo_base_url', $this->siteOptions->get('site_url')), '/').'/sitemap.xml';
        $lines[] = 'Sitemap: '.$sitemap;

        return implode("\n", $lines)."\n";
    }
}
