<?php

namespace Pcteckserv\CmsCore\Services\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\TwoFactorAuthentication;

/** Importa fatores antigos sem alterar a identidade nem desativar a proteção. */
class LegacyTwoFactorMigration
{
    public function migrate(Model $identity, string $guard): void
    {
        if (! Schema::hasTable('cms_account_factors')) {
            return;
        }
        DB::table('cms_account_factors')->where('guard', $guard)->whereNotNull('confirmed_at')
            ->orderBy('id')->chunkById(100, function ($rows) use ($identity): void {
                foreach ($rows as $row) {
                    if (! $identity->newQuery()->whereKey($row->account_id)->exists()) {
                        continue;
                    }
                    $key = ['user_type' => $identity->getMorphClass(), 'user_id' => $row->account_id];
                    if (TwoFactorAuthentication::query()->where($key)->exists()) {
                        continue;
                    }
                    // Uma APP_KEY incorreta deve interromper a migração, nunca retirar o 2FA.
                    TwoFactorAuthentication::create($key + [
                        'secret' => Crypt::decryptString($row->secret),
                        'recovery_codes' => $row->recovery_codes ? json_decode(Crypt::decryptString($row->recovery_codes), true, flags: JSON_THROW_ON_ERROR) : [],
                        'confirmed_at' => $row->confirmed_at,
                        'last_used_timestamp' => $row->last_step,
                        'version' => Str::random(40), 'expires_at' => null,
                    ]);
                }
            });
    }
}
