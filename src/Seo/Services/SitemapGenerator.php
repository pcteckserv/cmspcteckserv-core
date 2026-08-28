<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Support\Facades\Cache;
use Pcteckserv\CmsCore\Seo\DTOs\SitemapUrl;
use Pcteckserv\CmsCore\Seo\Events\SitemapGenerated;
use Pcteckserv\CmsCore\Seo\Support\SeoRegistry;

class SitemapGenerator
{
    public const CACHE_KEY = 'cms-core.seo.sitemap';

    public function __construct(private readonly SeoRegistry $registry)
    {
    }

    public function xml(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn (): string => $this->buildXml());
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function buildXml(): string
    {
        $items = [];

        foreach ($this->registry->sitemapProviders() as $provider) {
            $provider = is_string($provider) ? app($provider) : $provider;

            foreach ($provider->urls() as $url) {
                if ($url instanceof SitemapUrl) {
                    $items[] = $url;
                }
            }
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
