<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use Pcteckserv\CmsCore\Support\Navigation\AdminMenuRegistry;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app('view')->replaceNamespace('cms-core', dirname(__DIR__, 2).'/resources/views');
    }

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_menu_identifica_a_pagina_atual_e_oculta_grupos_sem_permissao(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $xpath = $this->sidebarXPath($response->getContent());

        $this->assertSame(1, $xpath->query('//aside/nav[@aria-label="Menu principal"]')->length);
        $this->assertSame(1, $xpath->query('//aside/nav//a[@aria-current="page"]')->length);
        $this->assertSame(route('admin.dashboard'), $xpath->query('//aside/nav//a[@aria-current="page"]')->item(0)->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//aside/nav//button')->length);
    }

    public function test_submenu_abre_na_pagina_atual_e_respeita_permissoes(): void
    {
        app(PermissionSynchronizer::class)->sync();
        $user = User::factory()->create();
        $user->cmsPermissions()->sync(Permission::query()->where('key', 'core.users.view')->pluck('id'));

        $response = $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $xpath = $this->sidebarXPath($response->getContent());

        $this->assertSame(1, $xpath->query('//aside//button[@aria-controls="cms-access-submenu" and @aria-expanded="true"]')->length);
        $this->assertSame(1, $xpath->query('//div[@id="cms-access-submenu" and contains(concat(" ", @class, " "), " show ")]')->length);
        $this->assertSame(route('admin.users.index'), $xpath->query('//aside/nav//a[@aria-current="page"]')->item(0)->getAttribute('href'));
        $this->assertSame(1, $xpath->query('//div[@id="cms-access-submenu"]/a')->length);
        $this->assertSame(0, $xpath->query('//aside//button[@aria-controls="cms-config-submenu" or @aria-controls="cms-system-submenu"]')->length);
    }

    public function test_menu_de_plugin_mantem_selecao_e_escapa_labels(): void
    {
        Route::middleware(['web', 'auth'])->get('/test-sidebar-catalogue', fn () => view('cms-core::admin.layouts.app'))->name('test.sidebar.catalogue');
        Route::getRoutes()->refreshNameLookups();
        $this->mock(AdminMenuRegistry::class)->shouldReceive('visible')->andReturn(collect([
            [
                'key' => 'catalogue',
                'label' => 'Catálogo <script>alert(1)</script>',
                'active' => 'test.sidebar.*',
                'children' => [
                    ['label' => 'Produtos', 'active' => 'test.sidebar.catalogue', 'route' => 'test.sidebar.catalogue'],
                ],
            ],
        ]));

        $response = $this->actingAs(User::factory()->create())
            ->get(route('test.sidebar.catalogue'))->assertOk();
        $xpath = $this->sidebarXPath($response->getContent());

        $this->assertSame(1, $xpath->query('//aside//button[@aria-controls="plugin-menu-catalogue" and @aria-expanded="true"]')->length);
        $this->assertSame(1, $xpath->query('//div[@id="plugin-menu-catalogue"]//a[@aria-current="page"]')->length);
        $this->assertSame(0, $xpath->query('//aside//script')->length);
        $this->assertSame('Catálogo <script>alert(1)</script>', $xpath->query('//aside//button[@aria-controls="plugin-menu-catalogue"]')->item(0)->textContent);
    }

    private function sidebarXPath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $document->loadHTML('<meta charset="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
