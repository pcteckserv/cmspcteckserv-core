<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Pcteckserv\CmsCore\Seo\Support\AuditedPage;
use Pcteckserv\CmsCore\Seo\Support\SeoRegistry;

class SeoAuditor
{
    public function __construct(private readonly SeoRegistry $registry)
    {
    }

    public function audit(AuditedPage $page): array
    {
        $results = [];

        foreach ($this->registry->auditRules() as $rule) {
            $rule = is_string($rule) ? app($rule) : $rule;
            $results = array_merge($results, $rule->evaluate($page));
        }

        return [
            'score' => $this->score($results),
            'results' => $results,
        ];
    }

    private function score(array $results): int
    {
        $score = 100;

        foreach ($results as $result) {
            $score -= match ($result['status'] ?? 'warning') {
                'critical' => 25,
                'warning' => 10,
                default => 3,
            };
        }

        return max(0, $score);
    }
}
