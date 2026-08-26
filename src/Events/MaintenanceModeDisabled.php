<?php

namespace Pcteckserv\CmsCore\Events;

class MaintenanceModeDisabled
{
    public function __construct(public readonly ?int $userId = null) {}
}
