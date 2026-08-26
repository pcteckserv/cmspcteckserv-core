<?php

namespace Pcteckserv\CmsCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Services\Backups\BackupVerificationService;

class VerifyBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $runId)
    {
    }

    public function handle(BackupVerificationService $verification): void
    {
        $verification->verify(BackupRun::query()->findOrFail($this->runId));
    }
}
