<?php

namespace Pcteckserv\CmsCore\Seo\DTOs;

use Carbon\CarbonInterface;

class SitemapUrl
{
    public function __construct(
        public readonly string $url,
        public readonly ?CarbonInterface $lastModified = null,
        public readonly ?string $changeFrequency = null,
        public readonly ?float $priority = null,
    ) {
    }
}
