<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class DisablePluginCommand extends Command
{
    protected $signature = 'cms:plugins:disable {plugin}';

    protected $description = 'Desativa um plugin CMS.';

    public function handle(PluginManager $plugins): int
    {
        $plugins->disable((string) $this->argument('plugin'));

        $this->info('Plugin desativado com sucesso.');

        return self::SUCCESS;
    }
}
