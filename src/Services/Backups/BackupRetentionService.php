<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;

class BackupRetentionService
{
    public function __construct(private readonly BackupStorageService $storage) {}

    public function apply(?BackupPlan $plan): int
    {
        if (! $plan || (! $plan->retention_days && ! $plan->retention_count)) {
            return 0;
        }
        $runs = $plan->runs()->where('protected', false)->whereIn('status', ['success', 'partial'])->orderByDesc('created_at')->orderByDesc('id')->get();
        $deleted = 0;
        foreach ($runs as $index => $run) {
            // Manter sempre a cópia mais recente; ficheiros protegidos não entram no limite.
            if ($index === 0) {
                continue;
            }
            $expired = $plan->retention_days && $run->created_at->lt(now()->subDays($plan->retention_days));
            $extra = $plan->retention_count && $index >= $plan->retention_count;
            if ($expired || $extra) {
                $this->delete($run);
                $deleted++;
            }
        }
        if ($deleted) {
            BackupAuditLog::create(['action' => 'backups.retention', 'result' => 'success', 'context' => ['plan_id' => $plan->id, 'deleted' => $deleted]]);
        }

        return $deleted;
    }

    public function delete(BackupRun $run): void
    {
        if ($run->protected || in_array($run->status, ['pending', 'running'], true)) {
            throw new RuntimeException('Este backup está protegido ou em execução.');
        }
        // Apagar primeiro o remoto: se falhar, manter a cópia local e o registo.
        if ($run->remote_path) {
            $destination = $run->storageDestination();
            if (! $destination) {
                throw new RuntimeException('O destino deste backup não está disponível.');
            }
            $this->storage->delete($destination, $run->remote_path);
        }
        if ($run->local_path) {
            $root = trim(config('cms-backups.local_path', 'cms-backups'), '/').'/';
            if (str_contains($run->local_path, '..') || str_contains($run->local_path, '\\') || ! str_starts_with($run->local_path, $root)) {
                throw new RuntimeException('Caminho local de backup inválido.');
            }
            $disk = Storage::disk(config('cms-backups.local_disk', 'local'));
            if ($disk->exists($run->local_path) && ! $disk->delete($run->local_path)) {
                throw new RuntimeException('Não foi possível eliminar o backup local.');
            }
        }
        $run->delete();
    }
}
