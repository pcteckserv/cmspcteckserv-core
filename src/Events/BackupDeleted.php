<?php

namespace Pcteckserv\CmsCore\Events;

use Pcteckserv\CmsCore\Models\BackupRun;

class BackupDeleted
{
    public function __construct(public readonly BackupRun $run)
    {
    }
}
