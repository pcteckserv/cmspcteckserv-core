<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupPlan;

class BackupRetentionService
{
    public function __construct(private readonly BackupStorageService $storage)
    {
    }

    public function apply(?BackupPlan $plan): int
    {
        if (! $plan) {
            return 0;
        }

        $query = $plan->runs()
            ->where('protected', false)
            ->whereIn('status', ['success', 'partial'])
            ->oldest();

        if ($plan->retention_days) {
            $query->where('created_at', '<', now()->subDays($plan->retention_days));
        }

        $deleted = 0;
        foreach ($query->get() as $run) {
            if ($this->deleteRunFiles($run)) {
                $run->delete();
                $deleted++;
            }
        }

        if ($plan->retention_count) {
            $extraRuns = $plan->runs()
                ->where('protected', false)
                ->whereIn('status', ['success', 'partial'])
                ->latest()
                ->skip($plan->retention_count)
                ->take(100)
                ->get();

            foreach ($extraRuns as $run) {
                if ($this->deleteRunFiles($run)) {
                    $run->delete();
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            BackupAuditLog::query()->create([
                'action' => 'backups.retention',
                'result' => 'success',
                'context' => ['plan_id' => $plan->id, 'deleted' => $deleted],
            ]);
        }

        return $deleted;
    }

    private function deleteRunFiles($run): bool
    {
        if ($run->local_path) {
            Storage::disk(config('cms-backups.local_disk', 'local'))->delete($run->local_path);
        }

        if ($run->remote_path && $run->destination) {
            $this->storage->delete($run->destination, $run->remote_path);
        }

        return true;
    }
}
