<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\InstalledPlugin;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Plugins\DTOs\AvailablePlugin;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use Pcteckserv\CmsCore\Plugins\PluginInstallResult;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Tests\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PluginsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2).'/src/Support/ComposerRepositoryCleaner.php';
        require_once dirname(__DIR__, 2).'/src/Support/ComposerCommand.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/DTOs/AvailablePlugin.php';
        require_once dirname(__DIR__, 2).'/src/Http/Controllers/Admin/PluginsController.php';
        require_once dirname(__DIR__, 2).'/src/Plugins/PluginInstaller.php';
        if (! class_exists(\Pcteckserv\CmsCore\Support\Navigation\AdminMenuRegistry::class)) {
            require_once dirname(__DIR__, 2).'/src/Support/Navigation/AdminMenuRegistry.php';
        }
        parent::setUp();
        $this->app->singleton(\Pcteckserv\CmsCore\Support\Navigation\AdminMenuRegistry::class);

        $this->app['view']->replaceNamespace('cms-core', dirname(__DIR__, 2).'/resources/views');
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth'])
            ->delete('/admin/plugins/{plugin}', [\Pcteckserv\CmsCore\Http\Controllers\Admin\PluginsController::class, 'destroy'])
            ->name('admin.plugins.destroy');
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
    }

    public function test_plugin_instalado_nao_aparece_na_lista_de_disponiveis(): void
    {
        $plugin = new InstalledPlugin(['slug' => 'contact-forms', 'package' => 'pcteckserv/cms-contact-forms', 'installed_version' => '1.0.0']);
        $this->mock(\Pcteckserv\CmsCore\Plugins\PluginManager::class)
            ->shouldReceive('all')->once()->andReturn(collect([$plugin]));
        $this->mock(PluginRepository::class)->shouldReceive('available')->once()
            ->andReturn(collect([$this->availablePlugin()]));

        $this->actingAs($this->superAdmin())->get(route('admin.plugins.index'))
            ->assertOk()->assertViewHas('availablePlugins', fn ($items) => $items->isEmpty());
    }

    public function test_eliminacao_exige_autenticacao_e_permissao(): void
    {
        $this->mock(PluginInstaller::class)->shouldNotReceive('uninstall');
        $this->delete(route('admin.plugins.destroy', 'contact-forms'))->assertRedirect();
        $this->actingAs(User::factory()->create())
            ->delete(route('admin.plugins.destroy', 'contact-forms'))->assertForbidden();
    }

    public function test_plugin_ativo_nao_pode_ser_eliminado(): void
    {
        InstalledPlugin::query()->create([
            'slug' => 'contact-forms', 'name' => 'pcteckserv/cms-contact-forms',
            'package' => 'pcteckserv/cms-contact-forms', 'label' => 'Contacto',
            'installed_version' => '1.0.0', 'status' => 'enabled',
        ]);
        $this->actingAs($this->superAdmin())->delete(route('admin.plugins.destroy', 'contact-forms'))
            ->assertSessionHas('cms_plugin_error', 'Desative o plugin antes de o eliminar.');
        $this->assertDatabaseHas((new InstalledPlugin())->getTable(), ['slug' => 'contact-forms']);
    }

    public function test_plugin_desativado_pode_ser_eliminado(): void
    {
        $this->mock(PluginInstaller::class)->shouldReceive('uninstall')->once()
            ->with('contact-forms')->andReturn(new PluginInstallResult(true, 'Plugin eliminado com sucesso.'));
        $this->actingAs($this->superAdmin())->delete(route('admin.plugins.destroy', 'contact-forms'))
            ->assertRedirect(route('admin.plugins.index'))->assertSessionHas('cms_plugin_success');
    }

    public function test_erro_do_repositorio_nao_expoe_detalhes_internos(): void
    {
        $process = new Process([PHP_BINARY, '-r', 'fwrite(STDERR, "private-server-path secret-token"); exit(1);']);
        $process->run();
        $this->mock(PluginRepository::class)->shouldReceive('available')
            ->once()->andThrow(new ProcessFailedException($process));

        $this->actingAs($this->superAdmin())->get(route('admin.plugins.index'))
            ->assertOk()->assertSee('Verifique a ligação e as credenciais')
            ->assertDontSee('private-server-path')->assertDontSee('secret-token');
    }

    public function test_area_de_plugins_lista_plugins_configurados(): void
    {
        config(['cms-plugins.plugins' => [
            'blog' => $this->pluginConfig(),
        ]]);

        $this->mock(PluginRepository::class)
            ->shouldReceive('available')
            ->once()
            ->andReturn(collect());

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.plugins.index'))
            ->assertOk()
            ->assertSee('Plugins')
            ->assertSee('Blog')
            ->assertSee('pcteckserv/cms-blog')
            ->assertSee('Não instalado');
    }

    public function test_area_de_plugins_lista_plugins_disponiveis_no_repositorio(): void
    {
        $this->app['view']->replaceNamespace('cms-core', dirname(__DIR__, 2).'/resources/views');

        config(['cms-plugins.plugins' => []]);

        $this->mock(PluginRepository::class)
            ->shouldReceive('available')
            ->once()
            ->andReturn(collect([$this->availablePlugin()]));

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.plugins.index'))
            ->assertOk()
            ->assertDontSee('Plugins disponíveis')
            ->assertSee('Formulários de contacto')
            ->assertSee('pcteckserv/cms-contact-forms')
            ->assertSee('Instalar');
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

    public function test_plugin_pode_ser_instalado_pela_area_administrativa(): void
    {
        $admin = $this->superAdmin();

        $repository = $this->mock(PluginRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with('contact-forms')
            ->andReturn($this->availablePlugin());

        $installer = $this->mock(PluginInstaller::class);
        $installer->shouldReceive('install')
            ->once()
            ->with([
                'package' => 'pcteckserv/cms-contact-forms',
                'version_constraint' => '*@dev',
                'slug' => 'contact-forms',
                'label' => 'Formulários de contacto',
                'description' => 'Criação, gestão e processamento de formulários de contacto.',
                'provider' => 'Pcteckserv\\CmsContactForms\\CmsContactFormsServiceProvider',
                'repository_type' => 'path',
                'repository_url' => '../plugins/cmspcteckserv-formularios-de-contacto',
                'version' => null,
            ])
            ->andReturn(new PluginInstallResult(true, 'Plugin instalado com sucesso.'));

        $this->actingAs($admin)
            ->post(route('admin.plugins.install'), [
                'plugin' => 'contact-forms',
            ])
            ->assertRedirect(route('admin.plugins.index'))
            ->assertSessionHas('cms_plugin_success', 'Plugin instalado com sucesso.');
    }

    public function test_instalacao_de_plugins_exige_permissao(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->post(route('admin.plugins.install'), [
                'plugin' => 'contact-forms',
            ])
            ->assertForbidden();
    }

    public function test_instalacao_de_plugins_valida_identificador(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('admin.plugins.install'), [
                'plugin' => 'plugin invalido',
            ])
            ->assertSessionHasErrors('plugin');
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

    private function availablePlugin(): AvailablePlugin
    {
        return new AvailablePlugin(
            slug: 'contact-forms',
            directory: 'cmspcteckserv-formularios-de-contacto',
            package: 'pcteckserv/cms-contact-forms',
            label: 'Formulários de contacto',
            description: 'Criação, gestão e processamento de formulários de contacto.',
            provider: 'Pcteckserv\\CmsContactForms\\CmsContactFormsServiceProvider',
            versionConstraint: '*@dev',
            repositoryPath: '../plugins/cmspcteckserv-formularios-de-contacto',
        );
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
