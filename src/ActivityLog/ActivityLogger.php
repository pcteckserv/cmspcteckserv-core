<?php

namespace Pcteckserv\CmsCore\ActivityLog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\ActivityLog\Events\ActivityLogged;
use Pcteckserv\CmsCore\Models\ActivityLog;

class ActivityLogger implements ActivityLoggerContract
{
    public function __construct(private readonly Sanitizer $sanitizer)
    {
    }

    public function log(
        string $action,
        ?string $category = null,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        array $oldValues = [],
        array $newValues = [],
        mixed $user = null,
    ): ?ActivityLog {
        if (! config('cms-core.activity_log.enabled', true)) {
            return null;
        }

        $request = $this->currentRequest();
        $user ??= $this->currentUser();

        $activityLog = ActivityLog::query()->create([
            'user_type' => $user instanceof Model ? $user->getMorphClass() : null,
            'user_id' => $user instanceof Model ? $user->getKey() : null,
            'action' => $action,
            'category' => $category ?? str($action)->before('.')->toString(),
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
            'url' => $request instanceof Request ? $request->fullUrl() : null,
            'http_method' => $request instanceof Request ? $request->method() : null,
            'properties' => $this->sanitizer->sanitize($properties),
            'old_values' => $this->sanitizer->sanitize($oldValues),
            'new_values' => $this->sanitizer->sanitize($newValues),
        ]);

        event(new ActivityLogged($activityLog));

        return $activityLog;
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }

    private function currentUser(): mixed
    {
        try {
            return Auth::user();
        } catch (\Throwable) {
            return null;
        }
    }
}
