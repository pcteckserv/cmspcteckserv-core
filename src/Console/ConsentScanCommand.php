<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentRouteDiscoverer;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentScanner;
use Pcteckserv\CmsCore\Models\ConsentScan;

class ConsentScanCommand extends Command
{
    protected $signature = 'consent:scan {--url=* : URL pública específica a analisar}';

    protected $description = 'Executa uma análise de cookies e tecnologias de tracking.';

    public function handle(ConsentRouteDiscoverer $discoverer, ConsentScanner $scanner): int
    {
        $urls = $discoverer->discover($this->option('url'));
        $scan = ConsentScan::query()->create(['status' => 'pending', 'urls' => $urls]);
        $scanner->scan($scan, $urls);
        $scan->refresh();

        $this->info("Análise {$scan->status}: {$scan->technologies_found} tecnologias encontradas.");

        return self::SUCCESS;
    }
}
