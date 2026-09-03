<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class SyncPluginsCommand extends Command
{
    protected $signature = 'cms:plugins:sync';

    protected $description = 'Sincroniza o catálogo de plugins configurado com a base de dados.';

    public function handle(PluginManager $plugins): int
    {
        $plugins->sync();

        $this->info('Plugins sincronizados com sucesso.');

        return self::SUCCESS;
    }
}
