<?php

namespace Pcteckserv\CmsCore\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Pcteckserv\CmsCore\Seo\Services\NotFoundTracker;

class TrackSeoNotFound
{
    public function __construct(private readonly NotFoundTracker $tracker)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($response instanceof Response && $response->getStatusCode() === 404) {
            $this->tracker->track($request);
        }

        return $response;
    }
}
