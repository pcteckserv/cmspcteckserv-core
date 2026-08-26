<?php

namespace Pcteckserv\CmsCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Services\Backups\BackupRetentionService;

class CleanupBackupsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(BackupRetentionService $retention): void
    {
        BackupPlan::query()->each(fn (BackupPlan $plan) => $retention->apply($plan));
    }
}
