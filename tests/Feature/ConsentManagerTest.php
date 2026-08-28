<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentScanner;
use Pcteckserv\CmsCore\Database\Seeders\ConsentManagerSeeder;
use Pcteckserv\CmsCore\Models\ConsentScan;
use Pcteckserv\CmsCore\Models\ConsentService;
use Tests\TestCase;

class ConsentManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConsentManagerSeeder::class);
    }

    public function test_banner_aparece_em_rotas_publicas(): void
    {
        Route::get('/consent-public-test', fn () => response('<html><body><h1>Página pública</h1></body></html>'));

        $this->get('/consent-public-test')
            ->assertOk()
            ->assertSee('cms-consent-root', false)
            ->assertSee('Utilização de cookies');
    }

    public function test_banner_nao_aparece_no_admin(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login')
            ->assertDontSee('cms-consent-root', false);
    }

    public function test_publicacao_com_incremento_altera_versao(): void
    {
        $manager = app(ConsentManagerContract::class);
        $initialVersion = $manager->settings()->version;

        $config = $manager->publish(true);

        $this->assertSame($initialVersion + 1, $config['version']);
    }

    public function test_plugins_conseguem_registar_servicos(): void
    {
        app(ConsentManagerContract::class)->registerService([
            'key' => 'plugin-statistics',
            'name' => 'Estatísticas do plugin',
            'category' => 'analytics',
            'domains' => ['analytics.exemplo.com'],
        ]);

        $this->assertDatabaseHas('cms_consent_services', [
            'key' => 'plugin-statistics',
            'review_status' => 'suggested',
        ]);
    }

    public function test_servico_desconhecido_detectado_requer_revisao_e_consentimento(): void
    {
        Http::fake([
            url('/unknown-tracker') => Http::response('<html><body><script src="https://tracker.exemplo.test/sdk.js"></script></body></html>'),
        ]);

        $scan = ConsentScan::query()->create(['status' => 'pending']);
        app(ConsentScanner::class)->scan($scan, ['/unknown-tracker']);

        $service = ConsentService::query()->where('key', 'unknown-tracker-exemplo-test')->firstOrFail();

        $this->assertTrue($service->requires_consent);
        $this->assertSame('requires_review', $service->review_status);
        $this->assertNull($service->category_id);
    }
}
