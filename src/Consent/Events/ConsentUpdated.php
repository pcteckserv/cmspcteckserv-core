<?php

namespace Pcteckserv\CmsCore\Consent\Events;

class ConsentUpdated
{
    public function __construct(public readonly string $anonymousUuid, public readonly int $version, public readonly array $categories)
    {
    }
}
