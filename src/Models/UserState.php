<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserState extends Model
{
    protected $table = 'cms_user_states';

    protected $fillable = ['state', 'last_login_at', 'direct_permissions_enabled'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'direct_permissions_enabled' => 'boolean',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
