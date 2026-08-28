<?php

namespace Pcteckserv\CmsCore\Support\Facades;

use Illuminate\Support\Facades\Facade;

class ActivityLogger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract::class;
    }
}
