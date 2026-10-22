<?php

namespace Tests\Feature;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Seo\Contracts\SitemapProviderInterface;
use Pcteckserv\CmsCore\Seo\DTOs\SitemapUrl;
use Pcteckserv\CmsCore\Seo\Services\SitemapGenerator;
use Pcteckserv\CmsCore\Seo\Support\Facades\Seo;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Tests\TestCase;

class AutomaticSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SiteOptions::class)->setMany(['seo_base_url' => 'https://example.test/']);
    }

    public function test_static_public_routes_are_discovered_without_executing_them(): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/automatic-page', fn () => throw new \LogicException('A página não deve ser executada.'));
            Route::view('/automatic-view', 'missing-view');
        });

        $xml = app(SitemapGenerator::class)->xml();
        $this->assertStringContainsString('<loc>https://example.test/automatic-page</loc>', $xml);
        $this->assertStringContainsString('<loc>https://example.test/automatic-view</loc>', $xml);
    }

    public function test_protected_technical_dynamic_and_excluded_routes_are_omitted(): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/protected-page', fn () => 'private')->middleware('auth');
            Route::get('/permission-page', fn () => 'private')->middleware('can:secret');
            Route::get('/signed-page', fn () => 'private')->middleware('signed');
            Route::get('/guest-page', fn () => 'login')->middleware('guest');
            Route::get('/custom-private-page', fn () => 'private')->middleware('custom.access');
            Route::get('/class-private-page', fn () => 'private')->middleware(Authenticate::class);
            Route::get('/products/{slug}', fn () => 'product');
            Route::get('/optional/{slug?}', fn () => 'product');
            Route::get('/hidden-page', fn () => 'hidden')->defaults('sitemap', false);
            Route::get('/admin/unprotected', fn () => 'admin');
            Route::get('/api/unprotected', fn () => 'api');
            Route::get('/data.json', fn () => '{}');
            Route::post('/submit-only', fn () => 'submitted');
            Route::redirect('/old-page', '/new-page');
            Route::domain('other.test')->get('/other-domain', fn () => 'other');
            Route::domain('{tenant}.example.test')->get('/tenant-page', fn () => 'tenant');
            Route::fallback(fn () => 'fallback');
        });
        Route::get('/non-web-page', fn () => 'api');

        $xml = app(SitemapGenerator::class)->xml();
        foreach ([
            'protected-page', 'permission-page', 'signed-page', 'guest-page',
            'custom-private-page', 'class-private-page', 'products/', 'optional/',
            'hidden-page', '/admin', '/api', 'data.json', 'submit-only', 'old-page',
            'other-domain', 'tenant-page', 'non-web-page', 'fallback', '/login',
            '/compilar', '/up', '/sitemap.xml', '/robots.txt', '/favicon.ico',
        ] as $path) {
            $this->assertStringNotContainsString($path, $xml);
        }
    }

    public function test_custom_path_exclusions_and_disabling_discovery_are_supported(): void
    {
        Route::get('/excluded/example', fn () => 'excluded')->middleware('web');
        Route::get('/included-example', fn () => 'included')->middleware('web');
        config(['cms-core.sitemap.excluded_paths' => ['excluded/*']]);

        $this->assertStringNotContainsString('/excluded/example', app(SitemapGenerator::class)->xml());
        $this->assertStringContainsString('/included-example', app(SitemapGenerator::class)->xml());

        config(['cms-core.sitemap.discover_routes' => false]);
        $this->assertStringNotContainsString('<loc>', app(SitemapGenerator::class)->xml());
    }

    public function test_route_and_domain_changes_invalidate_the_cached_sitemap(): void
    {
        $generator = app(SitemapGenerator::class);
        $generator->xml();
        Route::get('/new-public-page', fn () => 'new')->middleware('web');

        $this->assertStringContainsString('https://example.test/new-public-page', $generator->xml());

        app(SiteOptions::class)->setMany(['seo_base_url' => 'https://new.example.test']);
        $this->assertStringContainsString('https://new.example.test/new-public-page', $generator->xml());
        $this->assertStringNotContainsString('<loc>https://example.test/', $generator->xml());
    }

    public function test_old_cached_xml_is_replaced_automatically(): void
    {
        cache()->put(SitemapGenerator::CACHE_KEY, '<urlset></urlset>', 3600);
        Route::get('/discovered-page', fn () => 'page')->middleware('web');

        $this->assertStringContainsString('/discovered-page', app(SitemapGenerator::class)->xml());
    }

    public function test_plugin_urls_are_preserved_and_take_priority_without_duplicates(): void
    {
        Route::get('/shared-page', fn () => 'page')->middleware('web');
        Seo::registerSitemapProvider(new class implements SitemapProviderInterface
        {
            public function urls(): iterable
            {
                yield new SitemapUrl('https://example.test/shared-page', priority: 0.7);
                yield new SitemapUrl('https://example.test/products/dynamic-product');
            }
        });

        $xml = app(SitemapGenerator::class)->xml();
        $this->assertSame(1, substr_count($xml, '<loc>https://example.test/shared-page</loc>'));
        $this->assertStringContainsString('<priority>0.7</priority>', $xml);
        $this->assertStringContainsString('/products/dynamic-product', $xml);
    }
}
