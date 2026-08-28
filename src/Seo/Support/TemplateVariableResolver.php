<?php

namespace Pcteckserv\CmsCore\Seo\Support;

use Pcteckserv\CmsCore\Support\SiteOptions;

class TemplateVariableResolver
{
    public function __construct(
        private readonly SeoRegistry $registry,
        private readonly SiteOptions $siteOptions,
    ) {
    }

    public function resolve(string $template, mixed $subject = null, array $extra = []): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function (array $match) use ($subject, $extra): string {
            $key = $match[1];

            if (array_key_exists($key, $extra)) {
                return (string) $extra[$key];
            }

            $builtIn = [
                'site_name' => $this->siteOptions->get('seo_site_name', $this->siteOptions->get('site_title')),
                'page_title' => data_get($subject, 'title', data_get($subject, 'name', '')),
                'page_name' => data_get($subject, 'name', data_get($subject, 'title', '')),
                'current_year' => now()->year,
            ];

            if (array_key_exists($key, $builtIn)) {
                return (string) $builtIn[$key];
            }

            $resolver = $this->registry->templateVariables()[$key] ?? null;

            return is_callable($resolver) ? (string) $resolver($subject) : '';
        }, $template) ?? $template;
    }
}
