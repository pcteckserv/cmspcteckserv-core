<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class MaintenanceOnCommand extends Command
{
    protected $signature = 'cms:maintenance:on';
    protected $description = 'Ativa o modo de manutenção público do CMS.';

    public function handle(MaintenanceModeManager $maintenance): int
    {
        $maintenance->enable();
        $this->info('Modo de manutenção público ativado.');

        return self::SUCCESS;
    }
}
