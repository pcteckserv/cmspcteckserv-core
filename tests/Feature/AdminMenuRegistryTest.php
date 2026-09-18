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
        $registry = new AdminMenuRegistry();
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
