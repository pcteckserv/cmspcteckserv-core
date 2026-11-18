<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Models\ActivityLog;

class PruneActivityLogsCommand extends Command
{
    protected $signature = 'cms-core:activity-logs:prune';

    protected $description = 'Elimina logs de atividade antigos de acordo com a retenção configurada.';

    public function handle(): int
    {
        $retentionDays = config('cms-core.activity_log.retention_days');

        if ($retentionDays === null || $retentionDays === '') {
            $this->info('A retenção automática de logs de atividade está desativada.');

            return self::SUCCESS;
        }

        $days = filter_var($retentionDays, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 36500]]);
        if ($days === false) {
            $this->error('A retenção tem de ser um número inteiro positivo de dias. Nenhum log foi eliminado.');

            return self::FAILURE;
        }
        $cutoff = now()->subDays($days);
        $deleted = 0;
        ActivityLog::query()->where('created_at', '<', $cutoff)->select('id')->chunkById(1000, function ($logs) use (&$deleted, $cutoff): void {
            $deleted += ActivityLog::query()->whereKey($logs->modelKeys())->where('created_at', '<', $cutoff)->delete();
        });

        $this->info("Logs de atividade eliminados: {$deleted}.");

        return self::SUCCESS;
    }
}
