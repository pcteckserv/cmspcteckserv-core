<?php

namespace Pcteckserv\CmsCore\Services\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;

class CompleteAdminLogin
{
    public function handle(Request $request, Model $user, bool $remember): RedirectResponse
    {
        Auth::guard('web')->login($user, $remember);
        $request->session()->forget(PendingTwoFactorLogin::KEY);
        $request->session()->regenerate();

        if (method_exists($user, 'cmsState')) {
            $user->cmsState()->updateOrCreate([], [
                'state' => method_exists($user, 'cmsAccessState') ? $user->cmsAccessState() : 'active',
                'last_login_at' => now(),
            ]);
        }

        app(ActivityLoggerContract::class)->log(
            action: 'auth.login',
            category: 'authentication',
            description: 'Utilizador iniciou sessão.',
            subject: $user,
            user: $user
        );

        return redirect()->intended(route('admin.dashboard'));
    }
}
