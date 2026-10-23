<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Security\AccountFactor;
use Pcteckserv\CmsCore\Security\TwoFactor;

class ProfileController
{
    public function edit()
    {
        return response()->view('cms-core::admin.profile', ['user' => auth('web')->user()])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request)
    {
        $user = auth('web')->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:190', 'email' => ['required', 'email', 'max:254', Rule::unique($user::class, 'email')->ignore($user->getKey(), $user->getKeyName())],
            'current_password' => 'required|string|max:500', 'password' => 'nullable|string|min:12|max:200|confirmed',
        ]);
        if (! Hash::check($data['current_password'], $user->getAuthPassword())) {
            return back()->withErrors(['current_password' => 'A palavra-passe atual não está correta.']);
        }
        $changes = ['name' => $data['name'], 'email' => $data['email']];
        if (! empty($data['password'])) {
            $changes['password'] = Hash::make($data['password']);
            $changes['remember_token'] = null;
        }
        $user->forceFill($changes)->save();
        $request->session()->regenerate();
        $request->session()->put('cms_account.identity.web', ['id' => $user->getAuthIdentifier(), 'hash' => $user->getAuthPassword()]);
        $factor = AccountFactor::where('guard', 'web')->where('account_id', $user->getAuthIdentifier())->whereNotNull('confirmed_at')->first();
        if ($factor) {
            $request->session()->put('two_factor.web', app(TwoFactor::class)->fingerprint($factor, $user));
        }
        app(ActivityLoggerContract::class)->log(action: 'auth.profile_updated', category: 'authentication', description: 'Utilizador atualizou o próprio perfil.', subject: $user, user: $user);

        return back()->with('status', 'Perfil atualizado.');
    }
}
