<?php

namespace Pcteckserv\CmsCore\Support\Facades;

use Illuminate\Support\Facades\Facade;

class ConsentManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract::class;
    }
}
