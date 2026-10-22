<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Support\Navigation\AdminMenuRegistry;
use Tests\TestCase;

class AdminMenuRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_submenus_respeitam_permissoes_e_nao_aparecem_sem_o_plugin_pai(): void
    {
        Gate::shouldReceive('allows')->andReturnUsing(fn ($permission) => $permission === 'test.child.view');
        Route::get('/test-parent', fn () => 'ok')->name('test.parent');
        Route::get('/test-child', fn () => 'ok')->name('test.child');
        Route::getRoutes()->refreshNameLookups();
        $parent = InstalledPlugin::query()->create([
            'slug' => 'test-parent', 'name' => 'tests/parent', 'package' => 'tests/parent', 'label' => 'Pai', 'status' => 'enabled', 'installed_version' => '1.0.0',
        ]);
        InstalledPlugin::query()->create([
            'slug' => 'test-child', 'name' => 'tests/child', 'package' => 'tests/child', 'label' => 'Filho', 'status' => 'enabled', 'installed_version' => '1.0.0',
        ]);
        $registry = new AdminMenuRegistry;
        $registry->register('parent', 'Loja', 'test.parent', 'test.parent.view', 'test-parent', 'test.parent');
        $registry->register('child', 'Pagamento', 'test.child', 'test.child.view', 'test-child', 'test.child', 100, 'parent');
        $registry->register('hidden', 'Oculto', 'test.child', 'test.hidden.view', 'test-child', 'test.child', 101, 'parent');
        $items = $registry->visible();
        $this->assertCount(1, $items);
        $this->assertSame('Loja', $items->first()['label']);
        $this->assertSame(['Pagamento'], array_column($items->first()['children'], 'label'));
        $parent->update(['status' => 'disabled']);
        $this->assertCount(0, $registry->visible());
    }

    public function test_menu_respeita_ativacao_permissao_e_existencia_da_rota(): void
    {
        require_once dirname(__DIR__, 2).'/src/Support/Navigation/AdminMenuRegistry.php';
        $allowed = true;
        Gate::shouldReceive('allows')->with('test.forms.view')->andReturnUsing(function () use (&$allowed): bool {
            return $allowed;
        });
        Route::get('/test-plugin', fn () => 'ok')->name('test.plugin.index');
        Route::getRoutes()->refreshNameLookups();
        $plugin = InstalledPlugin::query()->create([
            'slug' => 'test-plugin', 'name' => 'tests/plugin', 'package' => 'tests/plugin',
            'label' => 'Teste', 'status' => 'enabled', 'installed_version' => '1.0.0',
        ]);
        $registry = new AdminMenuRegistry;
        $registry->register('test', 'Teste', 'test.plugin.index', 'test.forms.view', 'test-plugin', 'test.plugin.*');
        $this->assertCount(1, $registry->visible());
        $plugin->update(['status' => 'disabled']);
        $this->assertCount(0, $registry->visible());
        $plugin->update(['status' => 'enabled']);
        config(['cms-plugins.enabled' => false]);
        $this->assertCount(0, $registry->visible());
        config(['cms-plugins.enabled' => true]);
        $allowed = false;
        $this->assertCount(0, $registry->visible());
        $registry->register('missing', 'Ausente', 'missing.route', 'test.forms.view', 'test-plugin', 'missing.*');
        $this->assertCount(0, $registry->visible());
    }
}
