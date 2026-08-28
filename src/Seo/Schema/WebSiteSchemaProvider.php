<?php

namespace Pcteckserv\CmsCore\Seo\Schema;

use Pcteckserv\CmsCore\Seo\Contracts\SchemaProviderInterface;
use Pcteckserv\CmsCore\Support\SiteOptions;

class WebSiteSchemaProvider implements SchemaProviderInterface
{
    public function __construct(private readonly SiteOptions $siteOptions)
    {
    }

    public function supports(mixed $subject): bool
    {
        return true;
    }

    public function schema(mixed $subject, array $context = []): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteOptions->get('seo_site_name', $this->siteOptions->get('site_title')),
            'url' => $this->siteOptions->get('seo_base_url', $this->siteOptions->get('site_url')),
        ]);
    }
}
