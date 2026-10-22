<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Support\Facades\Cache;
use Pcteckserv\CmsCore\Seo\DTOs\SitemapUrl;
use Pcteckserv\CmsCore\Seo\Events\SitemapGenerated;
use Pcteckserv\CmsCore\Seo\Support\SeoRegistry;

class SitemapGenerator
{
    public const CACHE_KEY = 'cms-core.seo.sitemap';

    public function __construct(private readonly SeoRegistry $registry) {}

    public function xml(): string
    {
        $routeUrls = iterator_to_array(app(PublicRoutesSitemapProvider::class)->urls());
        $signature = hash('sha256', serialize($routeUrls));
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && ($cached['routes'] ?? null) === $signature && isset($cached['xml'])) {
            return $cached['xml'];
        }

        $xml = $this->buildXml($routeUrls);
        Cache::put(self::CACHE_KEY, ['routes' => $signature, 'xml' => $xml], now()->addHour());

        return $xml;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function buildXml(array $routeUrls): string
    {
        $items = [];

        foreach ($this->registry->sitemapProviders() as $provider) {
            $provider = is_string($provider) ? app($provider) : $provider;

            foreach ($provider->urls() as $url) {
                if ($url instanceof SitemapUrl) {
                    $items[$url->url] = $url;
                }
            }
        }

        foreach ($routeUrls as $url) {
            $items[$url->url] ??= $url;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($items as $item) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($item->url)."</loc>\n";
            $xml .= $item->lastModified ? '    <lastmod>'.$item->lastModified->toAtomString()."</lastmod>\n" : '';
            $xml .= $item->changeFrequency ? '    <changefreq>'.e($item->changeFrequency)."</changefreq>\n" : '';
            $xml .= $item->priority !== null ? '    <priority>'.number_format($item->priority, 1, '.', '')."</priority>\n" : '';
            $xml .= "  </url>\n";
        }

        event(new SitemapGenerated(count($items)));

        return $xml."</urlset>\n";
    }
}
