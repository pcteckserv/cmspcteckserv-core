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

        $deleted = ActivityLog::query()
            ->where('created_at', '<', now()->subDays((int) $retentionDays))
            ->delete();

        $this->info("Logs de atividade eliminados: {$deleted}.");

        return self::SUCCESS;
    }
}
