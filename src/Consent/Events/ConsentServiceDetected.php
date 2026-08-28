<?php

namespace Pcteckserv\CmsCore\Consent\Events;

use Pcteckserv\CmsCore\Models\ConsentService;

class ConsentServiceDetected
{
    public function __construct(public readonly ConsentService $service)
    {
    }
}
