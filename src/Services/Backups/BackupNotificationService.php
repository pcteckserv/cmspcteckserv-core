<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Mail;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use Throwable;

class BackupNotificationService
{
    public function notifyRunFinished(BackupRun $run): void
    {
        $plan = $run->plan;
        if (! $plan) {
            return;
        }

        $event = match ($run->status) {
            'failed' => 'backup_failed',
            'partial' => 'remote_upload_failed',
            'success' => 'backup_succeeded',
            default => null,
        };

        if ($event) {
            $this->sendForEvent($plan, $event, $run);
        }

        if ($run->status === 'success' && $plan->notify_recovery && $plan->last_alert_sent_at) {
            $this->sendForEvent($plan, 'recovered', $run, force: true);
            $plan->forceFill(['last_alert_signature' => null])->save();
        }
    }

    public function sendForEvent(BackupPlan $plan, string $event, ?BackupRun $run = null, bool $force = false): void
    {
        if (! $this->eventEnabled($plan, $event)) {
            return;
        }

        $emails = $this->emails($plan);
        if ($emails === []) {
            return;
        }

        $signature = sha1($event.'|'.($run?->failure_reason ?: $run?->status ?: ''));
        if (! $force && $this->recentDuplicate($plan, $signature)) {
            return;
        }

        try {
            Mail::raw($this->message($plan, $event, $run), function ($message) use ($emails, $event): void {
                $message->to($emails)->subject($this->subject($event));
            });

            $plan->forceFill([
                'last_alert_sent_at' => now(),
                'last_alert_signature' => $signature,
            ])->save();

            BackupAuditLog::query()->create([
                'backup_run_id' => $run?->id,
                'action' => 'backups.alert.sent',
                'result' => 'success',
                'context' => ['event' => $event],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            BackupAuditLog::query()->create([
                'backup_run_id' => $run?->id,
                'action' => 'backups.alert.failed',
                'result' => 'failed',
                'context' => ['event' => $event],
            ]);
        }
    }

    private function emails(BackupPlan $plan): array
    {
        return array_values(array_filter($plan->notification_emails ?: [], fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }

    private function eventEnabled(BackupPlan $plan, string $event): bool
    {
        $events = array_replace(config('cms-backups.notifications.events', []), $plan->notification_events ?: []);

        return (bool) ($events[$event] ?? false);
    }

    private function recentDuplicate(BackupPlan $plan, string $signature): bool
    {
        return $plan->last_alert_signature === $signature
            && $plan->last_alert_sent_at
            && $plan->last_alert_sent_at->gt(now()->subMinutes($plan->repeat_alert_after_minutes ?: 360));
    }

    private function subject(string $event): string
    {
        return '[CMS PCTECKSERV] '.match ($event) {
            'recovered' => 'Backups normalizados',
            'remote_upload_failed' => 'Upload remoto de backup falhou',
            'backup_missing' => 'Backup automático não executado',
            'backup_corrupted' => 'Backup corrompido',
            'backup_succeeded' => 'Backup concluído',
            default => 'Backup falhou',
        };
    }

    private function message(BackupPlan $plan, string $event, ?BackupRun $run): string
    {
        return implode(PHP_EOL, [
            $this->subject($event),
            '',
            'Projeto: '.config('app.name', 'CMS PCTECK'),
            'Plano: '.$plan->name,
            'Data: '.now()->format('d/m/Y H:i'),
            'Tipo: '.($run?->type ?: $plan->type),
            'Destino: '.($plan->destination?->protocol ?: 'local'),
            'Estado: '.($run?->status ?: $event),
            'Motivo: '.($run?->failure_reason ?: 'Consulte o painel administrativo para mais informações.'),
            'Próxima execução: '.optional($plan->next_run_at)->format('d/m/Y H:i'),
        ]);
    }
}
