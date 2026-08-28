<?php

namespace Pcteckserv\CmsCore\Consent\Events;

class ConsentRevoked
{
    public function __construct(public readonly string $category)
    {
    }
}
