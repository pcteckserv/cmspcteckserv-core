<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class MaintenanceOffCommand extends Command
{
    protected $signature = 'cms:maintenance:off';
    protected $description = 'Desativa o modo de manutenção público do CMS.';

    public function handle(MaintenanceModeManager $maintenance): int
    {
        $maintenance->disable();
        $this->info('Modo de manutenção público desativado.');

        return self::SUCCESS;
    }
}
