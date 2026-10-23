<?php

namespace Pcteckserv\CmsCore\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TwoFactor
{
    public function challengeIfNeeded(Request $request, string $guard, ?string $return = null)
    {
        $user = auth($guard)->user();
        if (! $user || ! Schema::hasTable('cms_account_factors')) {
            return null;
        }
        $factor = AccountFactor::where('guard', $guard)->where('account_id', $user->getAuthIdentifier())->whereNotNull('confirmed_at')->first();
        if (! $factor || hash_equals($this->fingerprint($factor, $user), (string) $request->session()->get('two_factor.'.$guard))) {
            return null;
        }
        $request->session()->put('two_factor.pending', ['guard' => $guard, 'id' => $user->getAuthIdentifier(), 'hash' => hash('sha256', $user->getAuthPassword()), 'expires' => now()->addMinutes(5)->timestamp]);
        $request->session()->put('two_factor.return', $return ?? route($guard === 'web' ? 'admin.dashboard' : 'customers.account'));
        auth($guard)->logout();
        $request->session()->regenerate();

        return redirect()->route('security.challenge');
    }

    public function secret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        for ($i = 0; $i < 32; $i++) {
            $result .= $alphabet[random_int(0, 31)];
        }

        return $result;
    }

    // RFC 6238 / HOTP dynamic truncation, SHA-1, 30 seconds, six digits.
    public function code(string $secret, int $step): string
    {
        $bits = '';
        foreach (str_split($secret) as $char) {
            $position = strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', $char);
            if ($position === false) {
                throw new \InvalidArgumentException('Invalid Base32 secret');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $key .= chr(bindec($byte));
            }
        }
        $hash = hash_hmac('sha1', pack('N2', intdiv($step, 4294967296), $step % 4294967296), $key, true);
        $offset = ord($hash[19]) & 15;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function consume(AccountFactor $factor, string $code, bool $recovery = true): bool
    {
        return DB::transaction(function () use ($factor, $code, $recovery) {
            $factor = AccountFactor::lockForUpdate()->findOrFail($factor->id);
            $step = intdiv(now()->timestamp, 30);
            if (preg_match('/^\d{6}$/D', $code)) {
                foreach ([$step - 1, $step, $step + 1] as $candidate) {
                    if ($candidate > ($factor->last_step ?? -1) && hash_equals($this->code($factor->secret, $candidate), $code)) {
                        $factor->update(['last_step' => $candidate]);

                        return true;
                    }
                }
            }
            if ($recovery && $factor->confirmed_at) {
                $codes = $factor->recovery_codes ?? [];
                foreach ($codes as $i => $hash) {
                    if (hash_equals($hash, hash('sha256', $code))) {
                        unset($codes[$i]);
                        $factor->update(['recovery_codes' => array_values($codes)]);

                        return true;
                    }
                }
            }

            return false;
        });
    }

    public function fingerprint(AccountFactor $factor, $user): string
    {
        return hash('sha256', $factor->secret.'|'.$user->getAuthPassword());
    }
}
