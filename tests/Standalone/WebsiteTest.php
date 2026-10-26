<?php

namespace Pcteckserv\CmsCore\Tests;

use Illuminate\Support\Facades\Blade;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Pcteckserv\CmsCore\Support\Website;

require_once __DIR__.'/SharedCustomersTest.php';

class WebsiteTest extends SharedCustomersTest
{
    public function test_menu_details_contact_links_and_location_reassignment_preserve_existing_menus(): void
    {
        $this->actingAs($this->admin());
        app(Website::class)->save('menus', [['slug' => 'antigo', 'name' => 'Antigo', 'location' => 'header', 'items' => []]]);
        $data = ['name' => 'Contactos', 'slug' => 'contactos', 'location' => 'header', 'items' => [
            ['id' => 'a', 'label' => 'Email', 'url' => 'mailto:geral@example.test', 'position' => 0, 'title' => 'Enviar email', 'description' => '<b>Contacto</b>', 'css_class' => 'contact-link', 'rel' => 'nofollow'],
            ['id' => 'b', 'label' => 'Telefone', 'url' => 'tel:+351924015505', 'position' => 1],
        ]];
        $this->put('/admin/website/menus', $data)->assertSessionHasNoErrors();
        $menus = collect(app(Website::class)->menus())->keyBy('slug');
        $this->assertSame('custom', $menus['antigo']['location']);
        $this->get('/menus/contactos')->assertOk()->assertSee('mailto:geral@example.test', false)->assertSee('title="Enviar email"', false)->assertSee('class="contact-link"', false)->assertSee('&lt;b&gt;Contacto&lt;/b&gt;', false)->assertSee('rel="nofollow"', false);
        $data['original_slug'] = 'contactos';
        $data['items'][0]['css_class'] = '" onclick=alert(1)';
        $this->put('/admin/website/menus', $data)->assertSessionHasErrors('items.0.css_class');
        $data['items'][0]['css_class'] = '';
        $data['items'][0]['url'] = 'mailto:bad%0a@example.test';
        $this->put('/admin/website/menus', $data)->assertSessionHasErrors('items.0.url');
        $this->put('/admin/website/menus', ['original_slug' => 'antigo', 'slug' => 'antigo', 'name' => 'Editado', 'location' => 'custom'])->assertSessionHasNoErrors();
        $this->assertCount(2, app(Website::class)->menus());
        $this->delete('/admin/website-menus/antigo')->assertRedirect();
        $this->assertCount(1, app(Website::class)->menus());
    }

    public function test_global_public_components_use_menu_locations_and_respect_disabled_footer(): void
    {
        $website = app(Website::class);
        $website->save('menus', [
            ['slug' => 'topo', 'name' => 'Topo', 'location' => 'header', 'items' => [['id' => 'h', 'parent' => '', 'position' => 0, 'label' => 'Menu no cabeçalho', 'url' => '/sobre', 'new_tab' => false]]],
            ['slug' => 'fundo', 'name' => 'Fundo', 'location' => 'footer', 'items' => [['id' => 'f', 'parent' => '', 'position' => 0, 'label' => 'Menu no rodapé', 'url' => '/contactos', 'new_tab' => false]]],
        ]);
        $this->assertStringContainsString('Menu no cabeçalho', Blade::render('<x-cms::public-header />'));
        $this->assertStringContainsString('Menu no rodapé', Blade::render('<x-cms-footer />'));
        app(SiteOptions::class)->setMany(['footer_enabled' => false]);
        $this->assertStringNotContainsString('Menu no rodapé', Blade::render('<x-cms-footer />'));
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
