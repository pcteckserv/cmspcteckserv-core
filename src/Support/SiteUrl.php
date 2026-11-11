<?php

namespace Pcteckserv\CmsCore\Support;

final class SiteUrl
{
    public function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function relative(string $url, ?array $bases = null): string
    {
        if (! $this->isSafe($url)) {
            return $url;
        }

        foreach ($bases ?? app(SiteOptions::class)->storedUrlBases() as $base) {
            $reference = parse_url($base);
            $target = parse_url($url);
            if (! is_array($reference) || ! is_array($target)
                || ! isset($reference['host'], $target['host'])
                || strtolower($reference['host']) !== strtolower($target['host'])
                || strtolower($reference['scheme'] ?? '') !== strtolower($target['scheme'] ?? '')
                || ($reference['port'] ?? $this->defaultPort($reference)) !== ($target['port'] ?? $this->defaultPort($target))) {
                continue;
            }

            $prefix = rtrim($reference['path'] ?? '', '/');
            $path = $target['path'] ?? '/';
            if ($path !== $prefix && ! str_starts_with($path, $prefix.'/')) {
                continue;
            }

            return '/'.ltrim(substr($path, strlen($prefix)), '/')
                .(isset($target['query']) ? '?'.$target['query'] : '')
                .(isset($target['fragment']) ? '#'.$target['fragment'] : '');
        }

        return $url;
    }

    public function absolute(string $url, ?array $bases = null): string
    {
        $url = $this->relative($url, $bases);

        return $this->isInternalPath($url) ? $this->base().$url : $url;
    }

    public function isInternalPath(string $url): bool
    {
        return str_starts_with($url, '/') && ! str_starts_with($url, '//') && $this->isSafe($url);
    }

    public function isSafe(string $url): bool
    {
        $parts = parse_url($url);

        return ! preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            && is_array($parts) && ! isset($parts['user']) && ! isset($parts['pass'])
            && (! isset($parts['scheme']) || in_array(strtolower($parts['scheme']), ['http', 'https'], true));
    }

    private function defaultPort(array $parts): int
    {
        return strtolower($parts['scheme'] ?? '') === 'https' ? 443 : 80;
    }
}
