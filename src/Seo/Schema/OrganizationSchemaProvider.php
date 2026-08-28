<?php

namespace Pcteckserv\CmsCore\Seo\Schema;

use Pcteckserv\CmsCore\Seo\Contracts\SchemaProviderInterface;
use Pcteckserv\CmsCore\Support\SiteOptions;

class OrganizationSchemaProvider implements SchemaProviderInterface
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
            '@type' => $this->siteOptions->get('seo_organization_type', 'Organization'),
            'name' => $this->siteOptions->get('seo_organization_name', $this->siteOptions->get('seo_site_name')),
            'url' => $this->siteOptions->get('seo_base_url', $this->siteOptions->get('site_url')),
            'logo' => $this->siteOptions->get('seo_organization_logo'),
            'telephone' => $this->siteOptions->get('seo_organization_phone'),
            'email' => $this->siteOptions->get('seo_organization_email'),
            'address' => $this->siteOptions->get('seo_organization_address'),
            'sameAs' => array_values(array_filter(explode("\n", (string) $this->siteOptions->get('seo_social_profiles', '')))),
        ]);
    }
}
