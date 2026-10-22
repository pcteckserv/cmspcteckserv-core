<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Seo\Contracts\SitemapProviderInterface;
use Pcteckserv\CmsCore\Seo\DTOs\SitemapUrl;
use Pcteckserv\CmsCore\Support\SiteOptions;

class PublicRoutesSitemapProvider implements SitemapProviderInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly SiteOptions $siteOptions,
    ) {}

    public function urls(): iterable
    {
        if (! config('cms-core.sitemap.discover_routes', true)) {
            return;
        }

        $baseUrl = rtrim((string) ($this->siteOptions->get('seo_base_url')
            ?: $this->siteOptions->get('site_url')
            ?: config('app.url')), '/');

        foreach ($this->router->getRoutes() as $route) {
            if ($this->isPublicPage($route, $baseUrl)) {
                yield new SitemapUrl($baseUrl.'/'.ltrim($route->uri(), '/'));
            }
        }
    }

    private function isPublicPage(Route $route, string $baseUrl): bool
    {
        if (! in_array('GET', $route->methods(), true)
            || $route->isFallback
            || str_contains($route->uri(), '{')
            || ($route->defaults['sitemap'] ?? null) === false) {
            return false;
        }

        if ($route->getDomain() !== null && $route->getDomain() !== parse_url($baseUrl, PHP_URL_HOST)) {
            return false;
        }

        $path = trim($route->uri(), '/');
        if (Str::is(config('cms-core.sitemap.excluded_paths', []), $path)
            || preg_match('/\.(?:xml|json|txt|ico|css|js|map|png|jpe?g|webp|svg|gif|pdf|zip)$/i', $path)) {
            return false;
        }

        if (Str::startsWith(ltrim($route->getActionName(), '\\'), [
            'Pcteckserv\\CmsCore\\',
            RedirectController::class,
        ])) {
            return false;
        }

        $middleware = $route->gatherMiddleware();
        if (! in_array('web', $middleware, true)) {
            return false;
        }

        foreach ($middleware as $entry) {
            if (! is_string($entry)) {
                return false;
            }

            $name = explode(':', $entry, 2)[0];
            if (! in_array($name, config('cms-core.sitemap.public_middleware', ['web', 'throttle', 'cache.headers']), true)) {
                return false;
            }
        }

        return true;
    }
}
