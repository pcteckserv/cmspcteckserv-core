<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pcteckserv\CmsCore\Http\Requests\Auth\TwoFactorRequest;
use Pcteckserv\CmsCore\Services\Auth\CompleteAdminLogin;
use Pcteckserv\CmsCore\Services\Auth\PendingTwoFactorLogin;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request, PendingTwoFactorLogin $pending): View|RedirectResponse
    {
        return $pending->user($request) ? view('cms-core::auth.two-factor-challenge') : redirect()->route('login');
    }

    public function store(TwoFactorRequest $request, PendingTwoFactorLogin $pending, TwoFactorManager $manager, CompleteAdminLogin $login): RedirectResponse
    {
        $user = $pending->user($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'O pedido de autenticação expirou. Inicie sessão novamente.']);
        }

        $factor = $manager->verify($user, $request->validated('two_factor_code'));
        if (! $factor) {
            return back()->withErrors(['two_factor_code' => 'O código indicado é inválido, já foi utilizado ou expirou.']);
        }

        $remember = (bool) $request->session()->get(PendingTwoFactorLogin::KEY.'.remember', false);
        $request->session()->put(TwoFactorManager::VERIFIED, $factor->version);

        return $login->handle($request, $user, $remember);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget([PendingTwoFactorLogin::KEY, TwoFactorManager::VERIFIED]);
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
