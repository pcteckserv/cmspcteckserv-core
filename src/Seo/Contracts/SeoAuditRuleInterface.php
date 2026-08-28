<?php

namespace Pcteckserv\CmsCore\Seo\Contracts;

use Pcteckserv\CmsCore\Seo\Support\AuditedPage;

interface SeoAuditRuleInterface
{
    /** @return array<int, array{status:string, code:string, message:string, recommendation:string}> */
    public function evaluate(AuditedPage $page): array;
}
