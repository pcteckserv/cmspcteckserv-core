<?php

namespace Pcteckserv\CmsCore\Consent\Events;

class ConsentVersionChanged
{
    public function __construct(public readonly int $oldVersion, public readonly int $newVersion)
    {
    }
}
