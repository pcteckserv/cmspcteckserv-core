<?php

namespace Pcteckserv\CmsCore\Events;

use Pcteckserv\CmsCore\Models\BackupRun;

class BackupStarted
{
    public function __construct(public readonly BackupRun $run)
    {
    }
}
