<?php

namespace Pcteckserv\CmsCore\Services\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Pcteckserv\CmsCore\Services\UserModelResolver;

class PendingTwoFactorLogin
{
    public const KEY = 'cms.two_factor.login';

    public function begin(Request $request, Model $user, bool $remember): void
    {
        $request->session()->regenerate(true);
        $request->session()->forget(TwoFactorManager::VERIFIED);
        $request->session()->put(self::KEY, [
            'id' => $user->getKey(), 'remember' => $remember,
            'password_hash' => hash('sha256', $user->getAuthPassword()),
            'expires' => now()->addMinutes(5)->timestamp,
        ]);
    }

    public function user(Request $request): ?Model
    {
        $pending = $request->session()->get(self::KEY);
        if (! is_array($pending) || ($pending['expires'] ?? 0) <= now()->timestamp) {
            $request->session()->forget(self::KEY);
            return null;
        }

        $class = app(UserModelResolver::class)->className();
        $user = $class::query()->find($pending['id']);
        if (! $user || ! hash_equals($pending['password_hash'], hash('sha256', $user->getAuthPassword()))
            || (method_exists($user, 'isCmsActive') && ! $user->isCmsActive())) {
            $request->session()->forget(self::KEY);
            return null;
        }

        return $user;
    }
}
