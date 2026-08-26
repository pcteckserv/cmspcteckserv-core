<?php

namespace Pcteckserv\CmsCore\Services\Maintenance;

class MaintenanceTemplate
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description,
        public readonly string $view,
    ) {}
}
