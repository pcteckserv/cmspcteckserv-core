<?php

namespace Pcteckserv\CmsCore\Consent\Scanner;

use Illuminate\Support\Facades\Route;

class ConsentRouteDiscoverer
{
    public function discover(array $manualUrls = []): array
    {
        $urls = collect(Route::getRoutes())
            ->filter(fn ($route): bool => in_array('GET', $route->methods(), true))
            ->map(fn ($route): string => '/'.ltrim($route->uri(), '/'))
            ->reject(fn (string $uri): bool => str_contains($uri, '{') || $this->isExcluded($uri))
            ->merge($manualUrls)
            ->map(fn (string $uri): string => '/'.ltrim($uri, '/'))
            ->unique()
            ->values()
            ->all();

        return $urls ?: ['/'];
    }

    private function isExcluded(string $uri): bool
    {
        foreach (['/admin', '/login', '/api', '/logout', '/maintenance/access'] as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                return true;
            }
        }

        return (bool) preg_match('/\.(css|js|png|jpe?g|gif|svg|webp|ico|pdf|zip)$/i', $uri);
    }
}
