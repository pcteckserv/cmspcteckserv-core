<?php

namespace Pcteckserv\CmsCore\Security;

use Closure;
use Illuminate\Support\Facades\Schema;

class RequireTwoFactor
{
    public function handle($request, Closure $next)
    {
        if (! Schema::hasTable('cms_account_factors') || $request->routeIs('security.*', 'logout', 'customers.logout', 'shop.logout')) {
            return $next($request);
        }
        foreach (['web', 'shop_customer'] as $guard) {
            $user = auth($guard)->user();
            if (! $user) {
                continue;
            }
            if ($guard === 'shop_customer') {
                $previous = $request->session()->get('shop_customer.identity');
                if (! $user->active || ($previous && $previous['id'] === $user->getAuthIdentifier() && ! hash_equals($user->getAuthPassword(), $previous['hash']))) {
                    auth($guard)->logout();

                    continue;
                }
                $request->session()->put('shop_customer.identity', ['id' => $user->getAuthIdentifier(), 'hash' => $user->getAuthPassword()]);
            }
            if ($guard === 'web' && $request->is('admin', 'admin/*') && method_exists($user, 'hasCmsRole') && $user->hasCmsRole('core.client') && ! $user->cmsRoles()->where('key', '!=', 'core.client')->exists()) {
                abort(403, 'A conta de cliente não tem acesso à administração.');
            }
            if ($response = app(TwoFactor::class)->challengeIfNeeded($request, $guard, $request->isMethod('get') ? $request->fullUrl() : null)) {
                return $response;
            }
        }

        return $next($request);
    }
}
