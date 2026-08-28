<?php

namespace Pcteckserv\CmsCore\Seo\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Pcteckserv\CmsCore\Seo\Contracts\SchemaProviderInterface;
use Pcteckserv\CmsCore\Seo\Contracts\SeoAuditRuleInterface;
use Pcteckserv\CmsCore\Seo\Contracts\SeoResolverInterface;
use Pcteckserv\CmsCore\Seo\Contracts\SitemapProviderInterface;

/**
 * @method static void registerResolver(string|SeoResolverInterface $resolver)
 * @method static void registerSchemaProvider(string|SchemaProviderInterface $provider)
 * @method static void registerSitemapProvider(string|SitemapProviderInterface $provider)
 * @method static void registerAuditRule(string|SeoAuditRuleInterface $rule)
 * @method static void registerTemplateVariable(string $name, callable $resolver)
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cms.seo.registry';
    }
}
