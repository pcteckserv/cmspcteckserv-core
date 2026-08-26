<?php

namespace Pcteckserv\CmsCore\Events;

class MaintenanceModeEnabled
{
    public function __construct(public readonly ?int $userId = null) {}
}
