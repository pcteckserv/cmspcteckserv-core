<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Jobs\CleanupBackupsJob;

class CleanupBackupsCommand extends Command
{
    protected $signature = 'cms:backup:cleanup';
    protected $description = 'Executa a retenção e limpeza de backups expirados.';

    public function handle(): int
    {
        CleanupBackupsJob::dispatch();
        $this->info('Limpeza de backups colocada em fila.');

        return self::SUCCESS;
    }
}
