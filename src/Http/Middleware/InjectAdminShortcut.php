<?php

namespace Pcteckserv\CmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class InjectAdminShortcut
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();

        if (str_contains($content, 'id="cms-admin-shortcut"')) {
            return $response;
        }

        $shortcut = view('cms-core::public.admin-shortcut')->render();

        if (preg_match('/<body\b[^>]*>/i', $content) === 1) {
            $content = preg_replace('/<body\b[^>]*>/i', '$0'.$shortcut, $content, 1) ?? $content;
        } else {
            $content = $shortcut.$content;
        }

        $response->setContent($content);
        $response->setPrivate();
        $response->headers->set('Vary', 'Cookie', false);

        return $response;
    }

    private function shouldInject(Request $request, mixed $response): bool
    {
        if (! $response instanceof Response || ! $request->isMethod('GET') || $request->user() === null) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'login', 'api', 'api/*') || ! Route::has('admin.dashboard')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains($contentType, 'text/html') || $contentType === '';
    }
}
