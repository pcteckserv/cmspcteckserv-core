<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Pcteckserv\CmsCore\Seo\DTOs\ResolvedSeo;
use Pcteckserv\CmsCore\Seo\Models\SeoMeta;
use Pcteckserv\CmsCore\Seo\Support\SeoRegistry;
use Pcteckserv\CmsCore\Seo\Support\TemplateVariableResolver;
use Pcteckserv\CmsCore\Support\SiteOptions;

class SeoManager
{
    public function __construct(
        private readonly SiteOptions $siteOptions,
        private readonly SeoRegistry $registry,
        private readonly TemplateVariableResolver $variables,
    ) {
    }

    public function for(mixed $subject = null, array $overrides = []): ResolvedSeo
    {
        $global = $this->globalDefaults();
        $module = $this->resolveFromRegisteredResolvers($subject);
        $model = $this->resolveFromModel($subject);
        $data = array_replace($global, $module, $model, array_filter($overrides, static fn ($value) => $value !== null && $value !== ''));

        $pageTitle = $data['title'] ?: data_get($subject, 'title', data_get($subject, 'name', $global['title']));
        $template = $this->siteOptions->get('seo_title_template', '{page_title} | {site_name}');
        $title = $this->variables->resolve($template, $subject, ['page_title' => $pageTitle]);
        $description = $data['description'] ?: $global['description'];
        $canonical = $data['canonical_url'] ?: ($this->truthy($this->siteOptions->get('seo_auto_canonical', true)) ? $this->currentUrl() : null);

        $schema = $data['schema_data'] ?: $this->resolveSchema($subject, [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $canonical,
        ]);

        return new ResolvedSeo(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            robotsIndex: $this->truthy($data['robots_index']),
            robotsFollow: $this->truthy($data['robots_follow']),
            ogTitle: $data['og_title'] ?: $title,
            ogDescription: $data['og_description'] ?: $description,
            ogImage: $data['og_image'] ?: $this->siteOptions->get('seo_default_og_image'),
            ogType: $data['og_type'] ?: 'website',
            twitterTitle: $data['twitter_title'] ?: ($data['og_title'] ?: $title),
            twitterDescription: $data['twitter_description'] ?: ($data['og_description'] ?: $description),
            twitterImage: $data['twitter_image'] ?: ($data['og_image'] ?: $this->siteOptions->get('seo_default_og_image')),
            twitterCard: $data['twitter_card'] ?: $this->siteOptions->get('seo_twitter_card', 'summary_large_image'),
            schema: $schema,
            excludeFromSitemap: $this->truthy($data['exclude_from_sitemap'] ?? false),
        );
    }

    private function globalDefaults(): array
    {
        return [
            'title' => $this->siteOptions->get('seo_default_title', $this->siteOptions->get('site_title')),
            'description' => $this->siteOptions->get('seo_default_description', $this->siteOptions->get('site_description')),
            'canonical_url' => null,
            'robots_index' => $this->siteOptions->get('seo_default_robots_index', true),
            'robots_follow' => $this->siteOptions->get('seo_default_robots_follow', true),
            'og_title' => null,
            'og_description' => null,
            'og_image' => $this->siteOptions->get('seo_default_og_image'),
            'og_type' => 'website',
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'twitter_card' => $this->siteOptions->get('seo_twitter_card', 'summary_large_image'),
            'schema_data' => [],
            'exclude_from_sitemap' => false,
        ];
    }

    private function resolveFromModel(mixed $subject): array
    {
        if (! $subject instanceof Model || ! method_exists($subject, 'seo')) {
            return [];
        }

        $seo = $subject->relationLoaded('seo') ? $subject->getRelation('seo') : $subject->seo()->first();

        return $seo instanceof SeoMeta ? array_filter($seo->only((new SeoMeta())->getFillable()), static fn ($value) => $value !== null && $value !== '') : [];
    }

    private function resolveFromRegisteredResolvers(mixed $subject): array
    {
        $resolved = [];

        foreach ($this->registry->resolvers() as $resolver) {
            $resolver = is_string($resolver) ? app($resolver) : $resolver;

            if ($resolver->supports($subject)) {
                $resolved = array_replace($resolved, $resolver->resolve($subject));
            }
        }

        return $resolved;
    }

    private function resolveSchema(mixed $subject, array $context): array
    {
        if (! $this->truthy($this->siteOptions->get('seo_generate_json_ld', true))) {
            return [];
        }

        $schemas = [];

        foreach ($this->registry->schemaProviders() as $provider) {
            $provider = is_string($provider) ? app($provider) : $provider;

            if ($provider->supports($subject)) {
                $schema = $provider->schema($subject, $context);

                if ($this->isValidSchema($schema)) {
                    $schemas[] = $schema;
                }
            }
        }

        return $schemas;
    }

    private function isValidSchema(array $schema): bool
    {
        return isset($schema['@context'], $schema['@type']) && $schema['@context'] === 'https://schema.org';
    }

    private function currentUrl(): ?string
    {
        return app()->bound('request') ? Request::fullUrl() : $this->siteOptions->get('seo_base_url');
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
