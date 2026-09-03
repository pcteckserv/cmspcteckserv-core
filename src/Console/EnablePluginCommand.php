<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class EnablePluginCommand extends Command
{
    protected $signature = 'cms:plugins:enable {plugin}';

    protected $description = 'Ativa um plugin CMS instalado.';

    public function handle(PluginManager $plugins): int
    {
        $plugins->enable((string) $this->argument('plugin'));

        $this->info('Plugin ativado com sucesso.');

        return self::SUCCESS;
    }
}
