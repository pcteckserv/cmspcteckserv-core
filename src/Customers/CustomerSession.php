<?php

namespace Pcteckserv\CmsCore\Customers;

use Closure;

class CustomerSession
{
    public function handle($request, Closure $next)
    {
        $guard = auth('shop_customer');
        $previous = $request->session()->get('shop_customer.identity');
        if (! $guard->user()?->active || ($previous && $previous['id'] === $guard->id() && ! hash_equals($guard->user()->getAuthPassword(), $previous['hash']))) {
            $guard->logout();

            return redirect()->route('customers.login');
        }
        $request->session()->put('shop_customer.identity', ['id' => $guard->id(), 'hash' => $guard->user()->getAuthPassword()]);

        return $next($request);
    }
}
