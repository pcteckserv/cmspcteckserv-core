<?php

namespace Pcteckserv\CmsCore\ActivityLog\Contracts;

use Illuminate\Database\Eloquent\Model;
use Pcteckserv\CmsCore\Models\ActivityLog;

interface ActivityLoggerContract
{
    public function log(
        string $action,
        ?string $category = null,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        array $oldValues = [],
        array $newValues = [],
        mixed $user = null,
    ): ?ActivityLog;
}
