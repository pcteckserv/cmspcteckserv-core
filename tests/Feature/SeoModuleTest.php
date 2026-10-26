<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Seo\Concerns\HasSeo;
use Pcteckserv\CmsCore\Seo\Contracts\SitemapProviderInterface;
use Pcteckserv\CmsCore\Seo\DTOs\SitemapUrl;
use Pcteckserv\CmsCore\Seo\Models\SeoAudit;
use Pcteckserv\CmsCore\Seo\Models\SeoNotFound;
use Pcteckserv\CmsCore\Seo\Models\SeoRedirect;
use Pcteckserv\CmsCore\Seo\Services\RedirectResolver;
use Pcteckserv\CmsCore\Seo\Services\SeoAuditor;
use Pcteckserv\CmsCore\Seo\Services\SeoManager;
use Pcteckserv\CmsCore\Seo\Services\SitemapGenerator;
use Pcteckserv\CmsCore\Seo\Support\AuditedPage;
use Pcteckserv\CmsCore\Seo\Support\Facades\Seo;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Tests\TestCase;

class SeoModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('seo_test_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function test_seo_manager_aplica_fallbacks_sociais(): void
    {
        $page = SeoTestPage::query()->create(['title' => 'Página de Teste']);
        $page->seo()->create(['description' => 'Descrição técnica da página.']);

        $seo = app(SeoManager::class)->for($page);

        $this->assertStringContainsString('Página de Teste', $seo->title);
        $this->assertSame($seo->title, $seo->ogTitle);
        $this->assertSame('Descrição técnica da página.', $seo->twitterDescription);
        $this->assertSame('index, follow', $seo->robotsContent());
    }

    public function test_plugins_conseguem_registar_template_variables_e_sitemap_urls(): void
    {
        Seo::registerTemplateVariable('category', fn () => 'Serviços');
        Seo::registerSitemapProvider(new class implements SitemapProviderInterface
        {
            public function urls(): iterable
            {
                yield new SitemapUrl('https://example.test/servicos');
            }
        });

        $seo = app(SeoManager::class)->for(null, ['title' => 'Serviços']);

        $this->assertStringContainsString('Serviços', $seo->title);
        $this->assertStringContainsString('<loc>https://example.test/servicos</loc>', app(SitemapGenerator::class)->xml());
    }

    public function test_titulo_da_pagina_respeita_seo_explicito_e_fallback_global(): void
    {
        app(SiteOptions::class)->setMany([
            'seo_default_title' => 'Título global', 'seo_title_template' => '{page_title}',
        ]);
        $manager = app(SeoManager::class);
        $this->assertSame('Título global', $manager->for()->title);
        $page = SeoTestPage::query()->create(['title' => 'Título da página']);
        $this->assertSame('Título da página', $manager->for($page)->title);
        $page->seo()->create(['title' => 'Título SEO']);
        $this->assertSame('Título SEO', $manager->for($page)->title);
        $this->assertSame('Título substituído', $manager->for($page, ['title' => 'Título substituído'])->title);
    }

    public function test_regras_de_auditoria_detectam_problemas_basicos(): void
    {
        $result = app(SeoAuditor::class)->audit(new AuditedPage('https://example.test', '<html><body><h1>A</h1><h1>B</h1><img src="/a.jpg"></body></html>'));

        $this->assertLessThan(100, $result['score']);
        $this->assertContains('missing_title', array_column($result['results'], 'code'));
        $this->assertContains('multiple_h1', array_column($result['results'], 'code'));
        $this->assertContains('missing_alt', array_column($result['results'], 'code'));
    }

    public function test_redirect_resolver_usa_redirects_ativos(): void
    {
        SeoRedirect::query()->create([
            'source' => '/antiga',
            'destination' => '/nova',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $redirect = app(RedirectResolver::class)->resolve('/antiga');

        $this->assertSame('/nova', $redirect?->destination);
        $this->assertSame(hash('sha256', '/antiga'), $redirect?->source_hash);
    }

    public function test_urls_seo_longos_usam_hashes_indexaveis(): void
    {
        $url = '/'.str_repeat('segmento-', 200);
        $notFound = SeoNotFound::query()->create([
            'url' => $url,
            'method' => 'GET',
        ]);
        $audit = SeoAudit::query()->create([
            'url' => 'https://example.test'.$url,
            'score' => 100,
        ]);

        $this->assertSame(hash('sha256', $url), $notFound->url_hash);
        $this->assertSame(hash('sha256', 'https://example.test'.$url), $audit->url_hash);
    }
}

class SeoTestPage extends Model
{
    use HasSeo;

    protected $table = 'seo_test_pages';

    protected $guarded = [];
}
