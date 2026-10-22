<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;
use Pcteckserv\CmsCore\Database\Seeders\ConsentManagerSeeder;
use Pcteckserv\CmsCore\Models\ConsentCategory;
use Pcteckserv\CmsCore\Models\ConsentService;
use Tests\TestCase;

class ConsentInstallationTest extends TestCase
{
    use RefreshDatabase;

    private function categoryMigration(): object
    {
        return require __DIR__.'/../../database/migrations/0001_01_01_000013_initialize_consent_categories.php';
    }

    public function test_migrations_instalam_categorias_sem_executar_seeders(): void
    {
        $this->assertDatabaseCount('cms_consent_categories', 4);
        $this->assertDatabaseHas('cms_consent_categories', [
            'key' => 'necessary',
            'name' => 'Necessários',
            'is_active' => true,
            'is_required' => true,
        ]);

        foreach (['preferences', 'analytics', 'marketing'] as $key) {
            $this->assertDatabaseHas('cms_consent_categories', [
                'key' => $key,
                'is_active' => true,
                'is_required' => false,
            ]);
        }
    }

    public function test_migration_corrige_instalacao_vazia_sem_publicar_rascunhos(): void
    {
        ConsentCategory::query()->delete();
        $manager = app(ConsentManagerContract::class);
        $published = $manager->publish();
        $settings = $manager->settings();
        $settings->update(['texts' => ['banner_title' => 'Texto ainda em revisão']]);

        $this->categoryMigration()->up();

        $this->assertDatabaseCount('cms_consent_categories', 4);
        $this->assertSame($published, $manager->publishedConfig());
        $this->assertSame($published, $settings->fresh()->published_config);
        $this->assertSame(['banner_title' => 'Texto ainda em revisão'], $settings->fresh()->texts);

        $config = $manager->publish();
        $this->assertCount(4, $config['categories']);
        $this->assertSame($published['version'], $config['version']);

        Route::middleware('web')->get('/consent-installation-test', fn () => response('<html><body>Teste</body></html>'));
        $this->get('/consent-installation-test')->assertOk()->assertSee('cms-consent-root', false);
    }

    public function test_migration_preserva_categorias_personalizadas_e_inativas(): void
    {
        ConsentCategory::query()->delete();
        $category = ConsentCategory::query()->create([
            'key' => 'necessary',
            'name' => 'Nome personalizado',
            'public_text' => 'Texto personalizado',
            'is_active' => false,
            'is_required' => false,
        ]);
        $original = $category->fresh()->getAttributes();

        $this->categoryMigration()->up();

        $this->assertDatabaseCount('cms_consent_categories', 1);
        $this->assertSame($original, $category->fresh()->getAttributes());
    }

    public function test_migration_pode_ser_repetida_sem_duplicar_categorias(): void
    {
        $original = ConsentCategory::query()->orderBy('id')->get()->toArray();

        $this->categoryMigration()->up();
        $this->categoryMigration()->up();

        $this->assertSame($original, ConsentCategory::query()->orderBy('id')->get()->toArray());
    }

    public function test_rollback_preserva_categorias_e_configuracao(): void
    {
        $manager = app(ConsentManagerContract::class);
        $category = ConsentCategory::query()->where('key', 'necessary')->firstOrFail();
        $service = ConsentService::query()->create([
            'key' => 'existing-service',
            'name' => 'Serviço existente',
            'category_id' => $category->id,
        ]);
        $published = $manager->publish();

        $this->categoryMigration()->down();

        $this->assertDatabaseCount('cms_consent_categories', 4);
        $this->assertSame($category->id, $service->fresh()->category_id);
        $this->assertSame($published, $manager->publishedConfig());
    }

    public function test_seeder_mantem_compatibilidade_com_carregamento_direto(): void
    {
        $this->assertTrue(class_exists(ConsentManagerSeeder::class));

        require_once __DIR__.'/../../database/seeders/ConsentManagerSeeder.php';

        $this->seed(ConsentManagerSeeder::class);
        $this->seed(ConsentManagerSeeder::class);

        $this->assertDatabaseCount('cms_consent_categories', 4);
    }

    public function test_migration_nao_ativa_banner_desativado(): void
    {
        ConsentCategory::query()->delete();
        $manager = app(ConsentManagerContract::class);
        $manager->settings()->update(['banner_enabled' => false]);
        $published = $manager->publish();

        $this->categoryMigration()->up();

        $this->assertFalse($manager->settings()->banner_enabled);
        $this->assertSame($published, $manager->publishedConfig());
        $this->assertFalse($manager->publish()['enabled']);
    }

    public function test_visitante_nao_pode_executar_migrations_pelo_painel(): void
    {
        $this->post('/admin/laravel-commands/migrate/run')->assertRedirect('/login');
    }

    public function test_utilizador_sem_permissao_nao_pode_executar_migrations(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/laravel-commands/migrate/run')
            ->assertForbidden();
    }
}
