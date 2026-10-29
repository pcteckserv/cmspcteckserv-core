<?php

namespace Pcteckserv\CmsCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pcteckserv\CmsCore\Services\Auth\PendingTwoFactorLogin;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*') && ($user = $request->user())) {
            $factor = app(TwoFactorManager::class)->enabled($user);
            if ($factor && ! hash_equals($factor->version, (string) $request->session()->get(TwoFactorManager::VERIFIED, ''))) {
                Auth::guard('web')->logout();
                app(PendingTwoFactorLogin::class)->begin($request, $user, false);
                return redirect()->route('two-factor.challenge');
            }
        }

        $response = $next($request);
        if ($request->is('admin/profile*', 'two-factor-challenge*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Referrer-Policy', 'no-referrer');
        }
        return $response;
    }
}
