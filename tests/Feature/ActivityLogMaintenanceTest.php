<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pcteckserv\CmsCore\Models\ActivityLog;
use Pcteckserv\CmsCore\Models\Role;
use Tests\TestCase;

class ActivityLogMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['key' => 'core.super_admin'], ['name' => 'Admin', 'is_protected' => true]);
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }

    public function test_default_month_applies_to_list_csv_and_pagination_without_hiding_an_older_details_page(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00:00', 'Europe/Lisbon'));
        $old = ActivityLog::create(['action' => 'older.audit', 'description' => 'Older audit', 'created_at' => now()->subMonths(2)]);
        ActivityLog::create(['action' => 'recent.audit', 'description' => 'Recent audit']);
        $this->actingAs($this->admin())->get(route('admin.activity-logs.index'))->assertOk()->assertViewHas('filters', fn ($filters) => $filters['date_from'] === '2026-10-18' && $filters['date_to'] === '2026-11-18')->assertSee('Recent audit')->assertDontSee('Older audit');
        $csv = $this->get(route('admin.activity-logs.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Recent audit', $csv);
        $this->assertStringNotContainsString('Older audit', $csv);
        $this->get(route('admin.activity-logs.show', $old))->assertOk()->assertSee('Older audit');
        $this->get(route('admin.activity-logs.index', ['date_from' => '2026-08-01', 'date_to' => '2026-11-18']))->assertOk()->assertSee('Older audit');
        for ($i = 0; $i < 26; $i++) {
            ActivityLog::create(['action' => 'pagination.audit']);
        }
        $html = $this->get(route('admin.activity-logs.index'))->assertOk()->assertSee('pagination')->getContent();
        $this->assertStringNotContainsString('class="w-5 h-5"', $html);
        $this->assertStringContainsString('date_from=2026-10-18', $html);
    }

    public function test_dates_use_administrator_timezone_and_invalid_filters_return_validation_errors(): void
    {
        config(['app.timezone' => 'UTC', 'cms-core.admin_timezone' => 'Europe/Lisbon']);
        ActivityLog::create(['action' => 'boundary.in', 'created_at' => '2026-06-01 23:15:00']);
        ActivityLog::create(['action' => 'boundary.out', 'created_at' => '2026-06-02 23:15:00']);
        $this->actingAs($this->admin())->get(route('admin.activity-logs.index', ['date_from' => '2026-06-02', 'date_to' => '2026-06-02']))->assertOk()->assertViewHas('logs', fn ($logs) => $logs->count() === 1 && $logs->first()->action === 'boundary.in');
        $this->getJson(route('admin.activity-logs.index', ['date_from' => 'invalid']))->assertUnprocessable();
        $this->getJson(route('admin.activity-logs.index', ['search' => ['bad']]))->assertUnprocessable();
        $this->getJson(route('admin.activity-logs.index', ['date_from' => '2026-06-02', 'date_to' => '2026-06-01']))->assertUnprocessable();
    }

    public function test_daily_pruning_uses_ninety_days_and_rejects_unsafe_retention_values(): void
    {
        $this->assertSame(90, config('cms-core.activity_log.retention_days'));
        ActivityLog::create(['action' => 'expired.audit', 'created_at' => now()->subDays(91)]);
        ActivityLog::create(['action' => 'kept.audit', 'created_at' => now()->subDays(89)]);
        foreach ([0, -1, 'invalid'] as $value) {
            config(['cms-core.activity_log.retention_days' => $value]);
            $this->artisan('cms-core:activity-logs:prune')->assertFailed();
            $this->assertDatabaseHas('cms_activity_logs', ['action' => 'expired.audit']);
        }
        config(['cms-core.activity_log.retention_days' => 90]);
        $this->artisan('cms-core:activity-logs:prune')->assertSuccessful();
        $this->assertDatabaseMissing('cms_activity_logs', ['action' => 'expired.audit']);
        $this->assertDatabaseHas('cms_activity_logs', ['action' => 'kept.audit']);
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'cms-core:activity-logs:prune'));
        $this->assertNotNull($event);
        $this->assertSame('45 3 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
