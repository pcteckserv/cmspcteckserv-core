<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Models\Role;
use Tests\TestCase;

class PluginsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_de_plugins_lista_plugins_configurados(): void
    {
        config(['cms-plugins.plugins' => [
            'blog' => $this->pluginConfig(),
        ]]);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.plugins.index'))
            ->assertOk()
            ->assertSee('Plugins')
            ->assertSee('Blog')
            ->assertSee('pcteckserv/cms-blog')
            ->assertSee('Não instalado');
    }

    public function test_plugin_pode_ser_ativado_e_desativado(): void
    {
        config(['cms-plugins.plugins' => [
            'core_tools' => $this->pluginConfig('core_tools', 'pcteckserv/cms-core'),
        ]]);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->put(route('admin.plugins.enable', 'core_tools'))
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('cms_plugin_success');

        $this->assertSame('enabled', InstalledPlugin::query()->where('slug', 'core_tools')->value('status'));

        $this->actingAs($admin)
            ->put(route('admin.plugins.disable', 'core_tools'))
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('cms_plugin_success');

        $this->assertSame('disabled', InstalledPlugin::query()->where('slug', 'core_tools')->value('status'));
    }

    public function test_gestao_de_plugins_exige_permissao(): void
    {
        config(['cms-plugins.plugins' => [
            'blog' => $this->pluginConfig(),
        ]]);

        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get(route('admin.plugins.index'))
            ->assertForbidden();

        $this->actingAs($plainUser)
            ->put(route('admin.plugins.enable', 'blog'))
            ->assertForbidden();
    }

    public function test_area_de_atualizacoes_tem_seccao_de_plugins(): void
    {
        config(['cms-core.updates.enabled' => false]);
        config(['cms-plugins.plugins' => [
            'blog' => $this->pluginConfig(),
        ]]);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.updates.index'))
            ->assertOk()
            ->assertSee('Core')
            ->assertSee('Plugins')
            ->assertSee('pcteckserv/cms-blog');
    }

    /**
     * @return array<string, string>
     */
    private function pluginConfig(string $slug = 'blog', string $package = 'pcteckserv/cms-blog'): array
    {
        return [
            'name' => $package,
            'package' => $package,
            'label' => $slug === 'blog' ? 'Blog' : 'Ferramentas Core',
            'description' => 'Gestão de artigos e categorias.',
            'provider' => 'Pcteckserv\\CmsBlog\\BlogServiceProvider',
        ];
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['key' => 'core.super_admin'],
            ['name' => 'Super Admin', 'is_protected' => true],
        );

        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }
}
