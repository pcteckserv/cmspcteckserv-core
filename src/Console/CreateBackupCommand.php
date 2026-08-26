<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Jobs\CreateBackupJob;

class CreateBackupCommand extends Command
{
    protected $signature = 'cms:backup {--plan=} {--type=full : database, files, media ou full} {--sync}';
    protected $description = 'Cria um backup manual através dos serviços do CMS.';

    public function handle(): int
    {
        CreateBackupJob::dispatch($this->option('plan') ? (int) $this->option('plan') : null, (string) $this->option('type'), 'manual');
        $this->info('Backup colocado em fila.');

        return self::SUCCESS;
    }
}
