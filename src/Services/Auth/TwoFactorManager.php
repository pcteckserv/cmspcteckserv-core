<?php

namespace Pcteckserv\CmsCore\Services\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Models\TwoFactorAuthentication;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorManager
{
    public const VERIFIED = 'cms.two_factor.verified';

    public const SETUP = 'cms.two_factor.setup';

    public function __construct(private readonly Google2FA $totp, private readonly ActivityLoggerContract $logger)
    {
    }

    public function query(Model $user): Builder
    {
        return TwoFactorAuthentication::query()
            ->where('user_type', $user->getMorphClass())->where('user_id', $user->getKey());
    }

    public function enabled(Model $user): ?TwoFactorAuthentication
    {
        return $this->query($user)->whereNotNull('confirmed_at')->first();
    }

    public function begin(Model $user): TwoFactorAuthentication
    {
        return DB::transaction(function () use ($user): TwoFactorAuthentication {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if ($this->enabled($user)) {
                throw ValidationException::withMessages(['two_factor' => 'A autenticação de dois fatores já está ativa.']);
            }

            return TwoFactorAuthentication::updateOrCreate([
                'user_type' => $user->getMorphClass(), 'user_id' => $user->getKey(),
            ], [
                'secret' => $this->totp->generateSecretKey(), 'recovery_codes' => [],
                'version' => Str::random(40), 'confirmed_at' => null,
                'last_used_timestamp' => null, 'expires_at' => now()->addMinutes(10),
            ]);
        });
    }

    public function setup(Model $user, ?string $version): ?array
    {
        $factor = $this->query($user)->whereNull('confirmed_at')->first();
        if (! $factor || ! $version || ! hash_equals($factor->version, $version)
            || ! $factor->expires_at || $factor->expires_at->isPast()) {
            return null;
        }

        $uri = $this->totp->getQRCodeUrl((string) config('app.name', 'CMS'), $user->email, $factor->secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd()));

        return ['secret' => $factor->secret, 'qr' => 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($uri))];
    }

    public function confirm(Model $user, string $version, #[\SensitiveParameter] string $code): array
    {
        return DB::transaction(function () use ($user, $version, $code): array {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $factor = $this->query($user)->lockForUpdate()->first();
            if (! $factor || $factor->confirmed_at || ! hash_equals($factor->version, $version)
                || ! $factor->expires_at || $factor->expires_at->isPast() || ! $this->consume($factor, $code, false)) {
                $this->invalidCode();
            }

            $codes = $this->newRecoveryCodes($factor);
            $factor->confirmed_at = now();
            $factor->expires_at = null;
            $factor->save();
            $this->audit('enabled', $user);

            return $codes;
        });
    }

    public function verify(Model $user, #[\SensitiveParameter] string $code): ?TwoFactorAuthentication
    {
        return DB::transaction(function () use ($user, $code): ?TwoFactorAuthentication {
            $factor = $this->query($user)->whereNotNull('confirmed_at')->lockForUpdate()->first();
            return $factor && $this->consume($factor, $code) ? $factor : null;
        });
    }

    public function manage(Model $user, #[\SensitiveParameter] string $code, bool $disable): array
    {
        return DB::transaction(function () use ($user, $code, $disable): array {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $factor = $this->query($user)->whereNotNull('confirmed_at')->lockForUpdate()->first();
            if (! $factor || ! $this->consume($factor, $code)) {
                $this->invalidCode();
            }

            $codes = $disable ? [] : $this->newRecoveryCodes($factor);
            $disable ? $factor->delete() : $factor->save();
            $this->audit($disable ? 'disabled' : 'recovery_codes_regenerated', $user);

            return $codes;
        });
    }

    private function consume(TwoFactorAuthentication $factor, #[\SensitiveParameter] string $code, bool $allowRecovery = true): bool
    {
        if (preg_match('/^[0-9]{6}$/D', $code)) {
            $timestamp = $this->totp->verifyKeyNewer($factor->secret, $code, $factor->last_used_timestamp ?? 0, 1);
            if ($timestamp !== false) {
                $factor->last_used_timestamp = $timestamp;
                $factor->save();
                return true;
            }
        }

        if ($allowRecovery) {
            $hashes = $factor->recovery_codes;
            foreach ($hashes as $index => $hash) {
                if (hash_equals($hash, hash('sha256', $code))) {
                    unset($hashes[$index]);
                    $factor->recovery_codes = array_values($hashes);
                    $factor->save();
                    return true;
                }
            }
        }

        return false;
    }

    private function newRecoveryCodes(TwoFactorAuthentication $factor): array
    {
        $codes = [];
        for ($index = 0; $index < 8; $index++) {
            $codes[] = bin2hex(random_bytes(16));
        }
        $factor->recovery_codes = array_map(fn (string $code): string => hash('sha256', $code), $codes);
        return $codes;
    }

    private function invalidCode(): never
    {
        throw ValidationException::withMessages(['two_factor_code' => 'O código indicado é inválido, já foi utilizado ou expirou.']);
    }

    private function audit(string $action, Model $user): void
    {
        $this->logger->log(
            action: 'auth.two_factor.'.$action,
            category: 'authentication',
            description: 'Configuração da autenticação de dois fatores alterada.',
            subject: $user,
            user: $user
        );
    }
}
