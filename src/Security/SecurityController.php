<?php

namespace Pcteckserv\CmsCore\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;

class SecurityController
{
    private function identity(string $guard): mixed
    {
        abort_unless(in_array($guard, ['web', 'shop_customer'], true), 404);
        $user = auth($guard)->user();
        abort_unless($user, 403);
        abort_if(($guard === 'shop_customer' && ! $user->active) || (method_exists($user, 'isCmsActive') && ! $user->isCmsActive()), 403);

        return $user;
    }

    public function edit(string $guard)
    {
        $user = $this->identity($guard);
        $factor = AccountFactor::where('guard', $guard)->where('account_id', $user->getAuthIdentifier())->first();
        $qr = $factor && ! $factor->confirmed_at ? app(AuthenticatorQr::class)->image($factor, $user->email) : null;

        return response()->view('cms-core::customers.security', compact('guard', 'user', 'factor', 'qr'))->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function setup(Request $r, string $guard, TwoFactor $twoFactor)
    {
        $user = $this->identity($guard);
        $this->password($r, $user);
        $factor = AccountFactor::firstOrNew(['guard' => $guard, 'account_id' => $user->getAuthIdentifier()]);
        abort_if($factor->confirmed_at, 409);
        $factor->fill(['secret' => $twoFactor->secret(), 'last_step' => null, 'recovery_codes' => null])->save();

        return back();
    }

    public function confirm(Request $r, string $guard, TwoFactor $twoFactor)
    {
        $user = $this->identity($guard);
        $this->password($r, $user);
        $d = $r->validate(['code' => 'required|string|max:30']);
        $factor = AccountFactor::where('guard', $guard)->where('account_id', $user->getAuthIdentifier())->whereNull('confirmed_at')->firstOrFail();
        if (! $twoFactor->consume($factor, $d['code'], false)) {
            throw ValidationException::withMessages(['code' => 'Código inválido ou já utilizado.']);
        }
        $codes = array_map(fn () => bin2hex(random_bytes(8)), range(1, 8));
        $factor->update(['confirmed_at' => now(), 'recovery_codes' => array_map(fn ($code) => hash('sha256', $code), $codes)]);
        $r->session()->put('two_factor.'.$guard, $twoFactor->fingerprint($factor->fresh(), $user));
        $this->audit('auth.two_factor_enabled', $user);

        return back()->with('recovery_codes', $codes)->with('status', '2FA ativado. Guarde os códigos de recuperação.');
    }

    public function disable(Request $r, string $guard, TwoFactor $twoFactor)
    {
        $user = $this->identity($guard);
        $this->password($r, $user);
        $d = $r->validate(['code' => 'required|string|max:30']);
        $factor = AccountFactor::where('guard', $guard)->where('account_id', $user->getAuthIdentifier())->firstOrFail();
        if (! $twoFactor->consume($factor, $d['code'])) {
            throw ValidationException::withMessages(['code' => 'Código inválido ou já utilizado.']);
        }
        $factor->delete();
        $r->session()->forget('two_factor.'.$guard);
        $this->audit('auth.two_factor_disabled', $user);

        return back()->with('status', '2FA desativado.');
    }

    public function challenge()
    {
        abort_unless(session('two_factor.pending.expires', 0) > now()->timestamp, 403);

        return response()->view('cms-core::customers.challenge')->header('Cache-Control', 'no-store');
    }

    public function verify(Request $r, TwoFactor $twoFactor)
    {
        $pending = $r->session()->get('two_factor.pending', []);
        abort_unless(($pending['expires'] ?? 0) > now()->timestamp && in_array($pending['guard'] ?? '', ['web', 'shop_customer'], true), 403);
        $d = $r->validate(['code' => 'required|string|max:30']);
        $guard = auth($pending['guard']);
        $user = $guard->getProvider()->retrieveById($pending['id']);
        abort_unless($user && hash_equals($pending['hash'], hash('sha256', $user->getAuthPassword())), 403);
        abort_if(($pending['guard'] === 'shop_customer' && ! $user->active) || (method_exists($user, 'isCmsActive') && ! $user->isCmsActive()), 403);
        $factor = AccountFactor::where('guard', $pending['guard'])->where('account_id', $pending['id'])->whereNotNull('confirmed_at')->firstOrFail();
        if (! $twoFactor->consume($factor, $d['code'])) {
            throw ValidationException::withMessages(['code' => 'Código inválido ou já utilizado.']);
        }
        $guard->login($user);
        $r->session()->regenerate();
        $r->session()->put('two_factor.'.$pending['guard'], $twoFactor->fingerprint($factor, $user));
        $r->session()->forget('two_factor.pending');
        $this->audit('auth.two_factor_verified', $user);
        $return = $r->session()->pull('two_factor.return');

        return redirect()->to($return && parse_url($return, PHP_URL_HOST) === $r->getHost() ? $return : route($pending['guard'] === 'web' ? 'admin.dashboard' : 'customers.account'));
    }

    private function password(Request $r, $user): void
    {
        $r->validate(['password' => 'required|string|max:500']);
        if (! Hash::check($r->input('password'), $user->getAuthPassword())) {
            throw ValidationException::withMessages(['password' => 'Palavra-passe incorreta.']);
        }
    }

    private function audit(string $action, $user): void
    {
        app(ActivityLoggerContract::class)->log(action: $action, category: 'authentication', description: 'Alteração ou validação da autenticação em dois passos.', subject: $user, user: $user);
    }
}
