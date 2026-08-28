<?php

namespace Pcteckserv\CmsCore\Seo\Schema;

use Pcteckserv\CmsCore\Seo\Contracts\SchemaProviderInterface;

class WebPageSchemaProvider implements SchemaProviderInterface
{
    public function supports(mixed $subject): bool
    {
        return true;
    }

    public function schema(mixed $subject, array $context = []): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $context['title'] ?? data_get($subject, 'title'),
            'description' => $context['description'] ?? null,
            'url' => $context['canonical_url'] ?? null,
        ]);
    }
}
