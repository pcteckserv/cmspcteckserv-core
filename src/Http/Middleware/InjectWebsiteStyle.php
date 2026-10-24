<?php

namespace Pcteckserv\CmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pcteckserv\CmsCore\Support\Website;

class InjectWebsiteStyle
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        if ($response instanceof Response && $request->isMethod('GET') && ! $request->is('admin*', 'login', 'api*', 'seguranca*') && str_contains((string) $response->headers->get('Content-Type'), 'text/html') && app(Website::class)->get('css', '') !== '') {
            $html = $response->getContent();
            if (str_contains($html, '</head>')) {
                $response->setContent(str_replace('</head>', '<link rel="stylesheet" href="'.e(route('website.css')).'" data-cms-custom-style></head>', $html));
            }
        }

        return $response;
    }
}
