<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Services\Auth\CompleteAdminLogin;
use Pcteckserv\CmsCore\Services\Auth\PendingTwoFactorLogin;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly ActivityLoggerContract $activityLogger)
    {
    }

    public function create(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('cms-core::auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $request->session()->forget([PendingTwoFactorLogin::KEY, TwoFactorManager::VERIFIED]);
        $provider = Auth::guard('web')->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
            $this->activityLogger->log(
                action: 'auth.login_failed',
                category: 'authentication',
                description: 'Tentativa de autenticação falhada.',
                properties: ['email' => $request->input('email')],
            );

            return back()
                ->withErrors(['email' => 'As credenciais indicadas não correspondem aos nossos registos.'])
                ->onlyInput('email');
        }

        if (method_exists($user, 'isCmsActive') && ! $user->isCmsActive()) {
            return back()
                ->withErrors(['email' => 'Esta conta encontra-se inativa.'])
                ->onlyInput('email');
        }

        $provider->rehashPasswordIfRequired($user, $credentials);

        if (app(TwoFactorManager::class)->enabled($user)) {
            app(PendingTwoFactorLogin::class)->begin($request, $user, $request->boolean('remember'));
            return redirect()->route('two-factor.challenge');
        }

        return app(CompleteAdminLogin::class)->handle($request, $user, $request->boolean('remember'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $this->activityLogger->log(
            action: 'auth.logout',
            category: 'authentication',
            description: 'Utilizador terminou sessão.',
            subject: $user,
            user: $user,
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(config('cms-core.auth.logout_redirect', '/'));
    }
}
