<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Geography\CountryCatalog;
use Pcteckserv\CmsCore\Geography\CountryNames;
use Pcteckserv\CmsCore\Geography\ImportCountry;
use Pcteckserv\CmsCore\Models\Permission;
use Tests\TestCase;

class GeographyCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app('view')->replaceNamespace('cms-core', dirname(__DIR__, 2).'/resources/views');
        Http::preventStrayRequests();
    }

    private function grant(array $keys): void
    {
        $ids = [];
        foreach ($keys as $key) {
            $ids[] = Permission::firstOrCreate(['key' => $key], ['label' => $key, 'group' => 'Geografia'])->id;
        }
        auth()->user()->cmsPermissions()->sync($ids);
    }

    public function test_catalogo_local_nao_precisa_de_api_ou_dados_importados(): void
    {
        $this->assertCount(249, CountryNames::all());
        $this->assertArrayHasKey('PT', app(CountryCatalog::class)->countries());
        $this->assertSame([], app(CountryCatalog::class)->regions('PT'));
        $this->get(route('cms-core.geography.script'))->assertOk();
    }

    public function test_importacao_e_edicao_exigem_permissoes_e_validam_payload(): void
    {
        $this->get('/admin/geography')->assertRedirect(route('login'));
        $this->post('/admin/geography', ['countries' => ['PT']])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get('/admin/geography')->assertForbidden();
        $this->grant(['core.geography.view']);
        $this->get('/admin/geography')->assertOk()->assertDontSee('Importar regiões');
        $this->post('/admin/geography', ['countries' => ['PT']])->assertForbidden();
        $this->grant(['core.geography.view', 'core.geography.update']);
        $this->postJson('/admin/geography', ['countries' => ['XX']])->assertUnprocessable();
        $this->assertSame(0, DB::table('cms_geography')->count());
        Http::fake([ImportCountry::URL => Http::response(['error' => false, 'data' => [['iso2' => 'PT', 'states' => [['name' => 'Lisbon', 'state_code' => '11']]]]])]);
        $this->post('/admin/geography', ['countries' => ['PT']])->assertRedirect(route('admin.geography.index'))->assertSessionHasNoErrors();
        $this->get('/admin/geography/PT')->assertOk()->assertSee('Lisboa');
        $input = ['_revision' => 1, 'name' => 'Portugal personalizado', 'regions' => [['code' => 'PT-11', 'name' => 'Lisboa']], 'new_region' => 'Região adicional'];
        $this->put('/admin/geography/PT', $input)->assertRedirect()->assertSessionHasNoErrors();
        $catalog = app(CountryCatalog::class);
        $this->assertSame('Portugal personalizado', $catalog->countries()['PT']);
        $this->assertCount(2, $catalog->regions('PT'));
        $this->assertSame('PT-11', $catalog->regionCode('PT', 'Lisbon'));
        $this->putJson('/admin/geography/PT', $input)->assertUnprocessable();
        $this->putJson('/admin/geography/PT', array_replace($input, ['_revision' => 2, 'name' => '<script>']))->assertUnprocessable();
        $this->get('/admin/geography/XX')->assertNotFound();
        $this->assertDatabaseHas('cms_activity_logs', ['action' => 'core.geography.imported']);
        $this->assertDatabaseHas('cms_activity_logs', ['action' => 'core.geography.updated']);
        $this->app['env'] = 'production';
        $this->post('/admin/geography', ['countries' => ['PT']])->assertStatus(419);
    }

    public function test_falha_da_api_preserva_dados_e_nao_guarda_campos_desconhecidos(): void
    {
        $this->actingAs(User::factory()->create());
        $this->grant(['core.geography.view', 'core.geography.update']);
        Http::fake([ImportCountry::URL => Http::response([], 503)]);
        $this->post('/admin/geography', ['countries' => ['PT'], 'api_key' => 'valor-de-teste'])->assertSessionHasErrors('countries');
        $this->assertSame(0, DB::table('cms_geography')->count());
        $this->assertNull(session('_old_input.api_key'));
    }

    public function test_migration_preserva_catalogo_anterior_e_permite_recuar(): void
    {
        Schema::dropIfExists('cms_geography');
        Schema::dropIfExists('cms_shop_geography');
        Schema::create('cms_shop_geography', function (Blueprint $table): void {
            $table->char('country', 2)->primary();
            $table->string('name', 100);
            $table->json('regions');
            $table->string('source', 100);
            $table->unsignedInteger('revision');
            $table->timestamps();
        });
        $row = ['country' => 'PT', 'name' => 'Nome anterior', 'regions' => json_encode([['code' => 'PT-11', 'name' => 'Lisboa', 'aliases' => ['Lisbon']]]), 'source' => 'local', 'revision' => 7, 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()];
        DB::table('cms_shop_geography')->insert($row);
        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_11_06_000001_create_cms_geography.php';
        $migration->up();
        $this->assertEquals((object) $row, DB::table('cms_geography')->sole());
        DB::table('cms_geography')->where('country', 'PT')->update(['name' => 'Nome atualizado', 'revision' => 8]);
        $migration->down();
        $this->assertFalse(Schema::hasTable('cms_geography'));
        $this->assertDatabaseHas('cms_shop_geography', ['country' => 'PT', 'name' => 'Nome atualizado', 'revision' => 8]);
    }
}
