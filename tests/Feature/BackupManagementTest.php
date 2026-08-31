<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\Backups\BackupNotificationService;
use Pcteckserv\CmsCore\Services\Backups\BackupSchedulerService;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_do_destino_e_encriptada_e_nao_e_substituida_quando_vazia(): void
    {
        $admin = $this->superAdmin();
        $destination = BackupDestination::query()->create([
            'name' => 'FTP',
            'disk' => 'local',
            'protocol' => 'ftp',
            'host' => 'ftp.example.test',
            'remote_path' => 'backups',
            'password' => 'segredo-inicial',
        ]);

        $this->assertNotSame('segredo-inicial', $destination->getRawOriginal('password'));
        $this->assertSame('segredo-inicial', $destination->password_plain);

        $this->actingAs($admin)
            ->put(route('admin.backups.destinations.update', $destination), [
                'name' => 'FTP',
                'disk' => 'local',
                'protocol' => 'ftp',
                'host' => 'ftp.example.test',
                'port' => 21,
                'username' => 'backups',
                'password' => '',
                'remote_path' => 'backups',
                'timeout' => 30,
                'passive' => '1',
                'ssl' => '0',
                'verify_ssl' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('segredo-inicial', $destination->fresh()->password_plain);
    }

    public function test_scheduler_calcula_proxima_execucao_diaria_e_semanal(): void
    {
        $scheduler = app(BackupSchedulerService::class);

        $daily = new BackupPlan(['frequency' => 'daily', 'run_at' => '02:00', 'timezone' => 'Europe/Lisbon']);
        $weekly = new BackupPlan(['frequency' => 'weekly', 'run_at' => '03:30', 'timezone' => 'Europe/Lisbon', 'weekdays' => [7]]);

        $this->assertSame(
            '2026-08-27 02:00:00',
            $scheduler->nextRunAt($daily, CarbonImmutable::parse('2026-08-26 11:15', 'Europe/Lisbon'))->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-08-30 03:30:00',
            $scheduler->nextRunAt($weekly, CarbonImmutable::parse('2026-08-26 11:15', 'Europe/Lisbon'))->format('Y-m-d H:i:s')
        );
    }

    public function test_emails_de_alerta_sao_validados_no_backend(): void
    {
        $admin = $this->superAdmin();
        $plan = BackupPlan::query()->create([
            'name' => 'Backup Diário',
            'type' => 'full',
            'frequency' => 'daily',
            'run_at' => '02:00',
            'timezone' => 'Europe/Lisbon',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.backups.plans.update', $plan), [
                'name' => 'Backup Diário',
                'type' => 'full',
                'frequency' => 'daily',
                'run_at' => '02:00',
                'retention_days' => 30,
                'compression' => 'zip',
                'storage_mode' => 'local',
                'notification_emails' => "admin@example.test\nemail-invalido",
                'alert_timing' => 'after_retries',
                'repeat_alert_after_minutes' => 360,
            ])
            ->assertSessionHasErrors('emails.1');
    }

    public function test_alerta_de_falha_e_enviado_sem_duplicar_no_periodo_configurado(): void
    {
        Mail::fake();

        $plan = BackupPlan::query()->create([
            'name' => 'Backup Diário',
            'type' => 'full',
            'frequency' => 'daily',
            'run_at' => '02:00',
            'timezone' => 'Europe/Lisbon',
            'notification_emails' => ['admin@example.test'],
            'notification_events' => ['backup_failed' => true],
            'repeat_alert_after_minutes' => 360,
        ]);

        $service = app(BackupNotificationService::class);
        $service->sendForEvent($plan, 'backup_failed');
        $service->sendForEvent($plan->fresh(), 'backup_failed');

        $this->assertNotNull($plan->fresh()->last_alert_sent_at);
        $this->assertSame(1, BackupAuditLog::query()->where('action', 'backups.alert.sent')->count());
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
