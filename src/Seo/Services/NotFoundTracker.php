<?php

namespace Pcteckserv\CmsCore\Seo\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Pcteckserv\CmsCore\Seo\Models\SeoNotFound;
use Throwable;

class NotFoundTracker
{
    public function track(Request $request): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        try {
            $record = SeoNotFound::query()->firstOrNew(
                ['url' => '/'.ltrim($request->path(), '/'), 'method' => $request->method()],
            );

            $record->fill([
                'referer' => $request->headers->get('referer'),
                'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString(),
                'ip_hash' => config('cms-core.seo.track_404_ip_hash', false) ? hash('sha256', (string) $request->ip().config('app.key')) : null,
                'first_seen_at' => $record->first_seen_at ?: now(),
                'last_seen_at' => now(),
            ]);
            $record->save();
            $record->increment('hits');
        } catch (Throwable) {
        }
    }

    private function shouldTrack(Request $request): bool
    {
        try {
            if (! Schema::hasTable('seo_not_found_errors')) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        return ! preg_match('/(?:favicon\.ico|\.map|\.css|\.js|\.png|\.jpe?g|\.webp|\.gif|\.svg)$/i', $request->path());
    }
}
