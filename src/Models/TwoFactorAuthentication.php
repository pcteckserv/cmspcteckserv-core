<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;

class TwoFactorAuthentication extends Model
{
    protected $table = 'cms_two_factor_authentications';

    protected $guarded = ['id'];

    protected $hidden = ['secret', 'recovery_codes', 'version', 'last_used_timestamp'];

    protected $casts = [
        'secret' => 'encrypted',
        'recovery_codes' => 'array',
        'last_used_timestamp' => 'integer',
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
