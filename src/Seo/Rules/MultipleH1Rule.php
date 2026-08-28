<?php

namespace Pcteckserv\CmsCore\Seo\Rules;

use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Support\AuditedPage;

class MultipleH1Rule implements SeoAuditRuleInterface
{
    public function evaluate(AuditedPage $page): array
    {
        preg_match_all('/<h1\b[^>]*>/i', $page->html, $matches);

        if (count($matches[0]) <= 1) {
            return [];
        }

        return [[
            'status' => 'warning',
            'code' => 'multiple_h1',
            'message' => 'A página tem mais do que um H1.',
            'recommendation' => 'Mantenha apenas um H1 principal por página.',
        ]];
    }
}
