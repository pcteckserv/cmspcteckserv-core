<?php

namespace Pcteckserv\CmsCore\Seo\Rules;

use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Support\AuditedPage;

class MissingDescriptionRule implements SeoAuditRuleInterface
{
    public function evaluate(AuditedPage $page): array
    {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'][^"\']+["\']/is', $page->html)) {
            return [];
        }

        return [[
            'status' => 'warning',
            'code' => 'missing_description',
            'message' => 'A página não tem meta description.',
            'recommendation' => 'Adicione uma descrição clara com cerca de 120 a 160 caracteres.',
        ]];
    }
}
