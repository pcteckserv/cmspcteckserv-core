<?php

namespace Pcteckserv\CmsCore\Seo\Rules;

use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Support\AuditedPage;

class MissingAltRule implements SeoAuditRuleInterface
{
    public function evaluate(AuditedPage $page): array
    {
        preg_match_all('/<img\b(?![^>]*\balt=)[^>]*>/i', $page->html, $matches);

        if ($matches[0] === []) {
            return [];
        }

        return [[
            'status' => 'warning',
            'code' => 'missing_alt',
            'message' => count($matches[0]).' imagem(ns) sem texto alternativo.',
            'recommendation' => 'Adicione alt descritivo às imagens informativas.',
        ]];
    }
}
