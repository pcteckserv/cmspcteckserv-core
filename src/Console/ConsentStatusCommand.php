<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Models\ConsentScan;
use Pcteckserv\CmsCore\Models\ConsentService;
use Pcteckserv\CmsCore\Models\ConsentTechnology;

class ConsentStatusCommand extends Command
{
    protected $signature = 'consent:status';

    protected $description = 'Mostra o estado resumido do Consent Manager.';

    public function handle(): int
    {
        $this->line('Serviços: '.ConsentService::query()->count());
        $this->line('Tecnologias: '.ConsentTechnology::query()->count());
        $this->line('Requerem revisão: '.ConsentService::query()->where('review_status', 'requires_review')->count());
        $this->line('Último scan: '.(ConsentScan::query()->latest()->first()?->status ?? 'sem análise'));

        return self::SUCCESS;
    }
}
