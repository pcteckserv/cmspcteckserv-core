<?php

namespace Pcteckserv\CmsCore\Seo\DTOs;

class ResolvedSeo
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $canonicalUrl,
        public readonly bool $robotsIndex,
        public readonly bool $robotsFollow,
        public readonly ?string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly ?string $ogImage,
        public readonly string $ogType,
        public readonly ?string $twitterTitle,
        public readonly ?string $twitterDescription,
        public readonly ?string $twitterImage,
        public readonly string $twitterCard,
        public readonly array $schema,
        public readonly bool $excludeFromSitemap = false,
    ) {
    }

    public function robotsContent(): string
    {
        return ($this->robotsIndex ? 'index' : 'noindex').', '.($this->robotsFollow ? 'follow' : 'nofollow');
    }
}
