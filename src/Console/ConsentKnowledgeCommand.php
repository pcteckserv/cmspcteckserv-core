<?php

namespace Pcteckserv\CmsCore\Console;

use Illuminate\Console\Command;
use Pcteckserv\CmsCore\Consent\Knowledge\ConsentKnowledgeBase;

class ConsentKnowledgeCommand extends Command
{
    protected $signature = 'consent:knowledge';

    protected $description = 'Lista assinaturas conhecidas do Consent Manager.';

    public function handle(ConsentKnowledgeBase $knowledgeBase): int
    {
        foreach ($knowledgeBase->all() as $signature) {
            $this->line(($signature['service_name'] ?? $signature['service_key']).' -> '.($signature['category'] ?? 'sem categoria'));
        }

        return self::SUCCESS;
    }
}
