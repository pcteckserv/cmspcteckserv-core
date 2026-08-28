<?php

namespace Pcteckserv\CmsCore\Seo\Rules;

use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Support\AuditedPage;

class MissingCanonicalRule implements SeoAuditRuleInterface
{
    public function evaluate(AuditedPage $page): array
    {
        if (preg_match('/<link[^>]+rel=["\']canonical["\']/is', $page->html)) {
            return [];
        }

        return [[
            'status' => 'warning',
            'code' => 'missing_canonical',
            'message' => 'A página não tem URL canonical.',
            'recommendation' => 'Defina uma canonical para reduzir ambiguidade de URLs.',
        ]];
    }
}
