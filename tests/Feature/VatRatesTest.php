<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Support\{SiteOptions, VatRates};
use Tests\TestCase;

class VatRatesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): \App\Models\User
    {
        $role=\Pcteckserv\CmsCore\Models\Role::firstOrCreate(['key'=>'core.super_admin'],['name'=>'Super Admin','is_protected'=>true]);
        $user=\App\Models\User::factory()->create(); $user->cmsRoles()->sync([$role->id]); return $user;
    }

    public function test_rates_are_configured_in_general_options_and_not_inferred(): void
    {
        $this->assertSame([], app(VatRates::class)->rates());
        $this->actingAs($this->superAdmin())->get(route('admin.site-options.edit'))->assertOk()->assertSee('Taxas de IVA');
        $this->put(route('admin.site-options.update'), ['site_title'=>'Ensaio','admin_email'=>'test@example.test','locale'=>'pt_PT','vat_rates'=>"23\n6,5\n0"])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(['23.00'=>'23.00%','6.50'=>'6.50%','0.00'=>'0.00%'],app(VatRates::class)->rates());
        $this->put(route('admin.site-options.update'), ['site_title'=>'Ensaio','admin_email'=>'test@example.test','locale'=>'pt_PT','vat_rates'=>"23\n101"])->assertSessionHasErrors('vat_rates');
        $this->assertSame(3,count(app(VatRates::class)->rates()));
        app(SiteOptions::class)->setMany(['vat_rates'=>'']);
        $this->assertSame([],app(VatRates::class)->rates());
    }
}
