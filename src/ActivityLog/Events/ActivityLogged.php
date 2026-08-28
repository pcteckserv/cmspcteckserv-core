<?php

namespace Pcteckserv\CmsCore\ActivityLog\Events;

use Pcteckserv\CmsCore\Models\ActivityLog;

class ActivityLogged
{
    public function __construct(public readonly ActivityLog $activityLog)
    {
    }
}
