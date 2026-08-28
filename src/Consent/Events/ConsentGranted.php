<?php

namespace Pcteckserv\CmsCore\Consent\Events;

class ConsentGranted
{
    public function __construct(public readonly string $category)
    {
    }
}
