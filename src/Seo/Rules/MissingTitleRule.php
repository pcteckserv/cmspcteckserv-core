<?php

namespace Pcteckserv\CmsCore\Seo\Rules;

use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Support\AuditedPage;

class MissingTitleRule implements SeoAuditRuleInterface
{
    public function evaluate(AuditedPage $page): array
    {
        if (preg_match('/<title[^>]*>\s*.+?\s*<\/title>/is', $page->html)) {
            return [];
        }

        return [[
            'status' => 'critical',
            'code' => 'missing_title',
            'message' => 'A página não tem título SEO.',
            'recommendation' => 'Defina um título único e descritivo.',
        ]];
    }
}
