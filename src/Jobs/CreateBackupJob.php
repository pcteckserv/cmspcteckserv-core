<?php

namespace Pcteckserv\CmsCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Services\Backups\BackupManager;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;
    public int $tries = 3;

    public function __construct(
        public readonly ?int $planId,
        public readonly string $type,
        public readonly string $origin = 'automatic',
        public readonly ?int $userId = null,
        public readonly ?string $storageMode = null,
    ) {
    }

    public function backoff(): array
    {
        return config('cms-backups.retry.backoff', [60, 300, 900]);
    }

    public function handle(BackupManager $manager): void
    {
        $plan = $this->planId ? BackupPlan::query()->findOrFail($this->planId) : null;
        $manager->create($plan, $this->type, $this->origin, $this->userId, $this->storageMode);
    }
}
