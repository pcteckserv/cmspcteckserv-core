<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly ActivityLoggerContract $activityLogger)
    {
    }

    public function create(): View
    {
        return view('cms-core::auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->activityLogger->log(
                action: 'auth.login_failed',
                category: 'authentication',
                description: 'Tentativa de autenticação falhada.',
                properties: ['email' => $request->input('email'), 'password' => $request->input('password')],
            );

            return back()
                ->withErrors(['email' => 'As credenciais indicadas não correspondem aos nossos registos.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if (method_exists($user, 'isCmsActive') && ! $user->isCmsActive()) {
            Auth::guard('web')->logout();

            return back()
                ->withErrors(['email' => 'Esta conta encontra-se inativa.'])
                ->onlyInput('email');
        }

        if (method_exists($user, 'cmsState')) {
            $user->cmsState()->updateOrCreate([], [
                'state' => method_exists($user, 'cmsAccessState') ? $user->cmsAccessState() : 'active',
                'last_login_at' => now(),
            ]);
        }

        $request->session()->regenerate();

        $this->activityLogger->log(
            action: 'auth.login',
            category: 'authentication',
            description: 'Utilizador iniciou sessão.',
            subject: $user,
            user: $user,
        );

        return redirect()->intended(route('admin.dashboard'));
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
