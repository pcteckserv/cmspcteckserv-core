<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class MaintenanceStatusCommand extends Command
{
    protected $signature = 'cms:maintenance:status';
    protected $description = 'Mostra o estado do modo de manutenção público do CMS.';

    public function handle(MaintenanceModeManager $maintenance): int
    {
        $settings = $maintenance->settings();

        $this->components->twoColumnDetail('Estado', $maintenance->isActive() ? 'ATIVO' : 'Inativo');
        $this->components->twoColumnDetail('Template', $settings['template_name']);
        $this->components->twoColumnDetail('Início', $settings['start_at']?->toDateTimeString() ?? 'Sem agendamento');
        $this->components->twoColumnDetail('Fim previsto', $settings['end_at']?->toDateTimeString() ?? 'Sem previsão');
        $this->components->twoColumnDetail('Acesso privado', $settings['access_enabled'] ? 'Ativo' : 'Inativo');

        return self::SUCCESS;
    }
}
