<?php

namespace Pcteckserv\CmsCore\Consent\Events;

use Pcteckserv\CmsCore\Models\ConsentTechnology;

class ConsentTechnologyDetected
{
    public function __construct(public readonly ConsentTechnology $technology)
    {
    }
}
