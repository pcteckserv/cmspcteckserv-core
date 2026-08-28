<?php

namespace Pcteckserv\CmsCore\Seo\Support;

class AuditedPage
{
    public function __construct(
        public readonly string $url,
        public readonly string $html,
        public readonly int $statusCode = 200,
    ) {
    }
}
