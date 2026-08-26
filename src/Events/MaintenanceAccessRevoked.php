<?php

namespace Pcteckserv\CmsCore\Events;

class MaintenanceAccessRevoked
{
    public function __construct(public readonly ?int $userId = null) {}
}
