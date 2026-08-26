<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Jobs\CreateBackupJob;
use Pcteckserv\CmsCore\Models\BackupSchedulerHeartbeat;
use Pcteckserv\CmsCore\Services\Backups\BackupSchedulerService;

class RunDueBackupsCommand extends Command
{
    protected $signature = 'cms:backup:run-due';
    protected $description = 'Coloca em fila os planos de backup que estão vencidos.';

    public function handle(BackupSchedulerService $scheduler): int
    {
        BackupSchedulerHeartbeat::query()->create(['ran_at' => now()]);

        foreach ($scheduler->duePlans() as $plan) {
            CreateBackupJob::dispatch($plan->id, $plan->type, 'automatic');
            $this->line('Plano em fila: '.$plan->name);
        }

        return self::SUCCESS;
    }
}
