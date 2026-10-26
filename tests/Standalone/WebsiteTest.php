<?php

namespace Pcteckserv\CmsCore\Tests;

use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Support\Website;

require_once __DIR__.'/SharedCustomersTest.php';

class WebsiteTest extends SharedCustomersTest
{
    public function test_removed_theme_settings_are_cleaned_without_affecting_accounts_or_policies(): void
    {
        $website = app(Website::class);
        $website->save('menus', [['slug' => 'unused']]);
        $website->save('css', 'body{color:red}');
        $website->save('policy.privacy', 'Privacidade preservada');
        $website->save('registration', true);
        $customer = $this->customer();
        $hash = $customer->password;
        $migration = require __DIR__.'/../../database/migrations/2026_10_26_160000_remove_website_menu_and_style_options.php';
        $migration->up();
        $migration->up();
        $this->assertDatabaseMissing('cms_site_options', ['key' => 'website.menus']);
        $this->assertDatabaseMissing('cms_site_options', ['key' => 'website.css']);
        $this->assertSame('Privacidade preservada', $website->policy('privacy'));
        $this->assertTrue($website->registration());
        $this->assertSame($hash, $customer->fresh()->password);
        $this->actingAs($this->admin());
        foreach (['menus', 'style'] as $section) {
            $this->get('/admin/website/'.$section)->assertNotFound();
            $this->put('/admin/website/'.$section, [])->assertNotFound();
        }
        $this->get('/admin/website/accounts')->assertOk()->assertDontSee('/admin/website/menus', false)->assertDontSee('/admin/website/style', false);
        $this->get('/politica-de-privacidade')->assertOk()->assertDontSee('data-cms-custom-style', false);
        $this->get('/site-custom.css')->assertNotFound();
        $this->get('/menus/unused')->assertNotFound();
    }

    public function test_local_legal_pages_take_precedence_over_core_fallback_with_and_without_route_cache(): void
    {
        Route::view('/termos-e-condicoes', 'cms-core::website.policy', ['title' => 'Termos do tema', 'text' => 'Texto do tema'])->middleware('web')->name('theme.terms');
        app(Website::class)->save('policy.terms', 'Texto global');
        $this->get('/termos-e-condicoes')->assertOk()->assertViewIs('cms-core::website.policy')->assertSee('Texto do tema')->assertDontSee('Texto global');
        $router = app('router');
        $router->setCompiledRoutes($router->getRoutes()->compile());
        $this->get('/termos-e-condicoes')->assertOk()->assertSee('Texto do tema');
        app(Website::class)->save('policy.privacy', 'Privacidade global');
        $this->get('/politica-de-privacidade')->assertOk()->assertSee('Privacidade global');
    }

    protected function admin(): CustomerTestUser
    {
        $user = $this->manager();
        $user->cmsRoles()->sync([Role::firstOrCreate(['key' => 'core.super_admin'], ['name' => 'Administrador', 'is_protected' => true])->id]);

        return $user;
    }

    public function test_registration_and_policies_work_without_plugins_and_keep_empty_values(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/website/accounts')->assertOk()->assertSee('Permitir o registo público');
        $this->put('/admin/website/accounts', ['registration' => 0])->assertSessionHasNoErrors();
        $this->get('/cliente/registar')->assertNotFound();
        $this->put('/admin/website/accounts', ['registration' => 1])->assertSessionHasNoErrors();
        $this->get('/cliente/registar')->assertOk()->assertDontSee('termos e condições');
        $this->get('/admin/website/policies')->assertOk()->assertSee('iframe');
        $this->put('/admin/website/policies', ['policies' => ['privacy' => "Privacidade\n<script>alert(1)</script>", 'cookies' => 'Cookies']])->assertSessionHasNoErrors();
        $this->get('/politica-de-privacidade')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/termos-e-condicoes')->assertNotFound();
        $this->assertSame([], app(Website::class)->missingPolicies());
        $this->put('/admin/website/policies', ['policies' => ['privacy' => '']])->assertSessionHasNoErrors();
        $this->get('/politica-de-privacidade')->assertNotFound();
        $this->assertArrayHasKey('privacy', app(Website::class)->missingPolicies());
    }

    public function test_store_managers_cannot_modify_global_settings(): void
    {
        $this->actingAs($this->manager());
        $this->get('/admin/website/policies')->assertForbidden();
        foreach (['accounts', 'policies'] as $section) {
            $this->put('/admin/website/'.$section, [])->assertForbidden();
        }
    }
}
