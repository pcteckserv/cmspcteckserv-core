<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Models\UserProfile;
use Pcteckserv\CmsCore\Security\AccountFactor;
use Pcteckserv\CmsCore\Security\TwoFactor;

class ProfileController
{
    public function edit()
    {
        return response()->view('cms-core::admin.profile', ['user' => auth('web')->user(), 'profile' => UserProfile::where('user_id', auth('web')->id())->first()])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request)
    {
        $user = auth('web')->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:190', 'email' => ['required', 'email', 'max:254', Rule::unique($user::class, 'email')->ignore($user->getKey(), $user->getKeyName())],
            'current_password' => 'required|string|max:500', 'password' => 'nullable|string|min:12|max:200|confirmed',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'remove_avatar' => 'nullable|boolean', 'biography' => 'nullable|string|max:2000',
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
        $profile = UserProfile::firstOrNew(['user_id' => $user->getKey()]);
        $oldAvatar = $profile->avatar_path;
        $newAvatar = $request->file('avatar')?->store('staff-avatars', 'public');
        if ($newAvatar || $request->boolean('remove_avatar')) {
            $profile->avatar_path = $newAvatar ?: null;
        }
        if ($request->has('biography')) {
            $profile->biography = $data['biography'] ?? null;
        }
        try {
            $profile->save();
        } catch (\Throwable $e) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $e;
        }
        if ($oldAvatar && $oldAvatar !== $profile->avatar_path && str_starts_with($oldAvatar, 'staff-avatars/')) {
            Storage::disk('public')->delete($oldAvatar);
        }
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
