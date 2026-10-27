<?php

namespace Pcteckserv\CmsCore\Customers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CustomerAuthReturn
{
    public static function link(string $route, ?string $target = null): string
    {
        $target ??= self::current(request());

        return $target && self::local($target) ? URL::temporarySignedRoute($route, now()->addMinutes(30), ['return_to' => $target]) : route($route);
    }

    public static function capture(Request $request): void
    {
        if (! $request->has('return_to')) {
            $request->session()->forget('customers.auth_return');

            return;
        }
        $target = $request->query('return_to');
        abort_unless(is_string($target) && strlen($target) <= 4096 && $request->hasValidSignature() && self::local($target), 403);
        $request->session()->put('customers.auth_return', ['url' => $target, 'expires' => now()->addMinutes(30)->timestamp]);
    }

    public static function current(Request $request): ?string
    {
        $saved = $request->session()->get('customers.auth_return', []);

        return ($saved['expires'] ?? 0) >= now()->timestamp && is_string($saved['url'] ?? null) && self::local($saved['url']) ? $saved['url'] : null;
    }

    public static function pull(Request $request): ?string
    {
        $target = self::current($request);
        $request->session()->forget('customers.auth_return');

        return $target;
    }

    private static function local(string $target): bool
    {
        if (str_contains($target, chr(92)) || preg_match('/[\x00-\x20]/', $target)) {
            return false;
        }
        $parts = parse_url($target);
        $base = parse_url(url('/'));
        if (! is_array($parts) || ! is_array($base) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $prefix = rtrim($base['path'] ?? '', '/');
        $path = $parts['path'] ?? '/';

        return in_array($parts['scheme'] ?? '', ['http', 'https'], true) && ($parts['scheme'] ?? '') === ($base['scheme'] ?? '') && strtolower($parts['host'] ?? '') === strtolower($base['host'] ?? '') && ($parts['port'] ?? null) === ($base['port'] ?? null) && ($prefix === '' || $path === $prefix || str_starts_with($path, $prefix.'/'));
    }
}
