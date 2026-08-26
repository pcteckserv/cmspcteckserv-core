<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;

class BackupStatusCommand extends Command
{
    protected $signature = 'cms:backup:status';
    protected $description = 'Mostra o estado resumido dos backups.';

    public function handle(): int
    {
        $this->info('Planos: '.BackupPlan::query()->count());
        $this->info('Execuções: '.BackupRun::query()->count());
        $this->info('Último estado: '.(BackupRun::query()->latest()->value('status') ?: 'sem execuções'));

        return self::SUCCESS;
    }
}
