<?php

namespace Pcteckserv\CmsCore\Security;

use Illuminate\Database\Eloquent\Model;

class AccountFactor extends Model
{
    protected $table = 'cms_account_factors';

    protected $guarded = ['id'];

    protected $hidden = ['secret', 'recovery_codes'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'recovery_codes' => 'encrypted:array', 'confirmed_at' => 'datetime', 'last_step' => 'integer'];
    }
}
