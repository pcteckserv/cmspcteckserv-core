<?php

namespace Pcteckserv\CmsCore\Seo\Support;

use Pcteckserv\CmsCore\Seo\Contracts\SchemaProviderInterface;
use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Contracts\SeoResolverInterface;
use Pcteckserv\CmsCore\Seo\Contracts\SitemapProviderInterface;

class SeoRegistry
{
    private array $resolvers = [];
    private array $schemaProviders = [];
    private array $sitemapProviders = [];
    private array $auditRules = [];
    private array $templateVariables = [];

    public function registerResolver(string|SeoResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function registerSchemaProvider(string|SchemaProviderInterface $provider): void
    {
        $this->schemaProviders[] = $provider;
    }

    public function registerSitemapProvider(string|SitemapProviderInterface $provider): void
    {
        $this->sitemapProviders[] = $provider;
    }

    public function registerAuditRule(string|SeoAuditRuleInterface $rule): void
    {
        $this->auditRules[] = $rule;
    }

    public function registerTemplateVariable(string $name, callable $resolver): void
    {
        $this->templateVariables[$name] = $resolver;
    }

    public function resolvers(): array
    {
        return $this->resolvers;
    }

    public function schemaProviders(): array
    {
        return $this->schemaProviders;
    }

    public function sitemapProviders(): array
    {
        return $this->sitemapProviders;
    }

    public function auditRules(): array
    {
        return $this->auditRules;
    }

    public function templateVariables(): array
    {
        return $this->templateVariables;
    }
}
