<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Models\SiteOption;
use Pcteckserv\CmsCore\Rules\SiteLink;
use Pcteckserv\CmsCore\Seo\Models\SeoMeta;
use Pcteckserv\CmsCore\Seo\Services\RobotsTxtGenerator;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Pcteckserv\CmsCore\Support\SiteUrl;
use Tests\TestCase;

class SiteUrlMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_options_follow_configured_url_even_with_warm_cache(): void
    {
        $this->legacyOptions();
        $options = app(SiteOptions::class);
        $options->setMany(['site_icon_url' => 'http://old.test/site/storage/icon.png', 'seo_default_og_image' => 'https://cdn.test/image.png']);
        $this->assertSame('/storage/icon.png', SiteOption::where('key', 'site_icon_url')->value('value'));
        $this->assertSame('https://new.test/cms', $options->get('site_url'));
        $this->assertSame('https://new.test/cms/storage/icon.png', $options->siteIconUrl());
        config(['app.url' => 'https://next.test']);
        $this->assertSame('https://next.test', $options->get('seo_base_url'));
        $this->assertSame('https://next.test/storage/icon.png', $options->siteIconUrl());
        $this->assertSame('https://cdn.test/image.png', $options->get('seo_default_og_image'));
        $this->assertSame('http://old.test/site', SiteOption::where('key', 'site_url')->value('value'));
    }

    public function test_legacy_absolute_media_and_seo_are_rebased_without_rewriting_database(): void
    {
        $this->legacyOptions();
        SiteOption::create(['key' => 'site_icon_url', 'value' => 'http://old.test/site/storage/old.png']);
        app(SiteOptions::class)->clearCache();
        $this->assertSame('https://new.test/cms/storage/old.png', app(SiteOptions::class)->siteIconUrl());
        $meta = new SeoMeta;
        $meta->setRawAttributes(['canonical_url' => 'http://old.test/site/terms?x=1#top', 'og_image' => 'https://cdn.test/og.png']);
        $this->assertSame('https://new.test/cms/terms?x=1#top', $meta->canonical_url);
        $this->assertSame('https://cdn.test/og.png', $meta->og_image);
        $meta->twitter_image = 'https://new.test/cms/storage/twitter.png';
        $this->assertSame('/storage/twitter.png', $meta->getAttributes()['twitter_image']);
        config(['app.url' => 'https://next.test']);
        $this->assertSame('https://next.test/storage/twitter.png', $meta->twitter_image);
    }

    public function test_origins_ports_and_subdirectory_boundaries_preserve_external_urls(): void
    {
        $this->legacyOptions();
        $urls = app(SiteUrl::class);
        foreach (['http://old.test.evil/site/a', 'http://old.test:8080/site/a', 'http://old.test/site-other/a', 'https://external.test/a'] as $external) {
            $this->assertSame($external, $urls->absolute($external));
        }
        $this->assertSame('https://new.test/cms/terms?x=1#top', $urls->absolute('http://old.test:80/site/terms?x=1#top'));
    }

    public function test_link_validation_rejects_unsafe_inputs_and_accepts_internal_or_external_links(): void
    {
        foreach (['//evil.test', '/\\evil.test', "https://test.test/\npath", 'javascript:alert(1)', 'ftp://external.test/file', 'https://user:pass@test.test/a', 'relative/path'] as $url) {
            $this->assertTrue(Validator::make(['url' => $url], ['url' => ['string', new SiteLink]])->fails(), $url);
        }
        foreach (['/terms', '/privacy?lang=pt#top', 'https://external.test/terms'] as $url) {
            $this->assertFalse(Validator::make(['url' => $url], ['url' => ['string', new SiteLink]])->fails(), $url);
        }
    }

    public function test_admin_cannot_replace_configured_url_or_write_environment_file(): void
    {
        $this->legacyOptions();
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['key' => 'core.super_admin'], ['name' => 'Super Admin', 'is_protected' => true]);
        $user->cmsRoles()->sync([$role->id]);
        $environmentPath = $this->app->environmentFilePath();
        $before = is_file($environmentPath) ? hash_file('sha256', $environmentPath) : null;
        $this->actingAs($user)->put(route('admin.site-options.update'), [
            'site_title' => 'Site de teste', 'site_url' => 'https://manipulated.test',
            'admin_email' => 'admin@example.test', 'locale' => 'pt_PT',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('https://new.test/cms', config('app.url'));
        $this->assertSame($before, is_file($environmentPath) ? hash_file('sha256', $environmentPath) : null);
        $this->assertSame('http://old.test/site', SiteOption::where('key', 'site_url')->value('value'));
    }

    public function test_default_favicon_respects_installation_subdirectory(): void
    {
        config(['app.url' => 'https://new.test/cms']);
        $this->assertStringStartsWith('/cms/favicon.ico?v=', app(SiteOptions::class)->siteIconUrl());
    }

    public function test_robots_cache_changes_with_domain(): void
    {
        $this->legacyOptions();
        $robots = app(RobotsTxtGenerator::class);
        $this->assertStringContainsString('https://new.test/cms/sitemap.xml', $robots->text());
        config(['app.url' => 'https://next.test']);
        $this->assertStringContainsString('https://next.test/sitemap.xml', $robots->text());
    }

    private function legacyOptions(): void
    {
        config(['app.url' => 'https://new.test/cms']);
        SiteOption::create(['key' => 'site_url', 'value' => 'http://old.test/site']);
        SiteOption::create(['key' => 'seo_base_url', 'value' => 'http://old.test/site']);
        app(SiteOptions::class)->clearCache();
    }
}
