<?php

namespace Pcteckserv\CmsCore\Consent\Events;

use Pcteckserv\CmsCore\Models\ConsentScan;

class ConsentScanCompleted
{
    public function __construct(public readonly ConsentScan $scan)
    {
    }
}
