<?php

namespace Pcteckserv\CmsCore\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pcteckserv\CmsCore\Seo\Services\RedirectResolver;

class HandleSeoRedirects
{
    public function __construct(private readonly RedirectResolver $redirects)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $redirect = $this->redirects->resolve($request->path());

        if ($redirect && $redirect->destination !== '/'.ltrim($request->path(), '/')) {
            $redirect->forceFill([
                'hits' => $redirect->hits + 1,
                'last_hit_at' => now(),
            ])->save();

            return redirect($redirect->destination, $redirect->status_code);
        }

        return $next($request);
    }
}
