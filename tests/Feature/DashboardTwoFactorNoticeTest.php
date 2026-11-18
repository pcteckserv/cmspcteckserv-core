<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;
use Tests\TestCase;

class DashboardTwoFactorNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function administrator(string $key = 'core.admin'): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['key' => $key], ['name' => 'Administrador de teste', 'is_protected' => false]);
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }

    public function test_administrators_and_superadmins_without_two_factor_receive_a_direct_profile_link(): void
    {
        foreach (['core.admin', 'core.super_admin'] as $key) {
            $this->actingAs($this->administrator($key))->get(route('admin.dashboard'))->assertOk()
                ->assertSee('data-admin-two-factor-notice', false)
                ->assertSee(route('admin.profile.edit').'#two-factor-title', false);
        }
    }

    public function test_pending_setup_keeps_the_notice_without_exposing_the_secret(): void
    {
        $user = $this->administrator();
        $factor = app(TwoFactorManager::class)->begin($user);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('data-admin-two-factor-notice', false)->assertDontSee($factor->secret);
    }

    public function test_confirmed_two_factor_hides_the_notice(): void
    {
        $user = $this->administrator();
        $factor = app(TwoFactorManager::class)->begin($user);
        $factor->forceFill(['confirmed_at' => now(), 'expires_at' => null])->save();

        $this->actingAs($user)->withSession([TwoFactorManager::VERIFIED => $factor->version])
            ->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-admin-two-factor-notice', false);
    }

    public function test_other_users_do_not_receive_an_administrator_notice(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('data-admin-two-factor-notice', false);
    }
}
