<?php

namespace Pcteckserv\CmsCore\Tests;

use Illuminate\Support\Facades\Blade;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Support\Website;

require_once __DIR__.'/SharedCustomersTest.php';

class WebsiteTest extends SharedCustomersTest
{
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

    public function test_menus_support_hierarchy_order_links_and_reject_cycles_or_script_urls(): void
    {
        $this->actingAs($this->admin());
        $data = ['name' => 'Principal', 'slug' => 'principal', 'location' => 'header', 'items' => [
            ['id' => 'a', 'label' => 'Empresa', 'url' => '/empresa', 'position' => 2],
            ['id' => 'b', 'label' => 'Contacto', 'url' => 'https://example.test/contacto', 'parent' => 'a', 'position' => 0, 'new_tab' => 1],
            ['id' => 'c', 'label' => 'Início', 'url' => '/', 'position' => 0],
        ]];
        $this->put('/admin/website/menus', $data)->assertSessionHasNoErrors();
        $this->get('/admin/website/menus?menu=principal')->assertOk()->assertSee('x-cms-menu');
        $this->get('/menus/principal')->assertOk()->assertSeeInOrder(['Início', 'Empresa', 'Contacto'])->assertSee('rel="noopener noreferrer"', false);
        $this->assertStringContainsString('Empresa', Blade::render('<x-cms-menu location="header" />'));
        $data['original_slug'] = 'principal';
        $data['items'][0]['parent'] = 'b';
        $this->put('/admin/website/menus', $data)->assertSessionHasErrors('items');
        unset($data['items'][0]['parent']);
        $data['items'][0]['url'] = 'javascript:alert(1)';
        $this->put('/admin/website/menus', $data)->assertSessionHasErrors('items.0.url');
        $this->delete('/admin/website-menus/principal')->assertRedirect();
        $this->get('/menus/principal')->assertNotFound();
    }

    public function test_css_is_delivered_as_css_and_only_injected_in_public_html(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/website/style')->assertOk();
        $this->put('/admin/website/style', ['css' => 'body{color:red}'])->assertSessionHasNoErrors();
        $this->get('/site-custom.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8')->assertSee('body{color:red}', false);
        app(Website::class)->save('policy.privacy', 'Texto');
        $this->get('/politica-de-privacidade')->assertSee('data-cms-custom-style', false);
        $this->get('/admin/website/style')->assertDontSee('data-cms-custom-style', false);
        $this->put('/admin/website/style', ['css' => ''])->assertSessionHasNoErrors();
        $this->get('/politica-de-privacidade')->assertDontSee('data-cms-custom-style', false);
    }

    public function test_store_managers_cannot_modify_global_settings(): void
    {
        $this->actingAs($this->manager());
        $this->get('/admin/website/policies')->assertForbidden();
        foreach (['accounts', 'policies', 'style', 'menus'] as $section) {
            $this->put('/admin/website/'.$section, [])->assertForbidden();
        }
        $this->delete('/admin/website-menus/principal')->assertForbidden();
    }
}
