<?php

namespace Pcteckserv\CmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;

class InjectConsentManager
{
    public function __construct(private readonly ConsentManagerContract $consentManager)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        $markup = view('cms-core::consent.banner', [
            'config' => $this->consentManager->publishedConfig(),
        ])->render();

        if (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $markup.'</body>', $content);
        } else {
            $content .= $markup;
        }

        $response->setContent($content);

        return $response;
    }

    private function shouldInject(Request $request, mixed $response): bool
    {
        if (! $response instanceof Response || ! $request->isMethod('GET')) {
            return false;
        }

        if ($request->is('admin*') || $request->is('login') || $request->is('api*')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains($contentType, 'text/html') || $contentType === '';
    }
}
