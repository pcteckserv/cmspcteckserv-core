<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledPlugin extends Model
{
    protected $table = 'cms_plugins';

    protected $fillable = [
        'slug',
        'name',
        'package',
        'label',
        'description',
        'provider',
        'installed_version',
        'available_version',
        'status',
        'metadata',
        'installed_at',
        'enabled_at',
        'disabled_at',
        'checked_at',
        'last_error',
    ];

    protected $casts = [
        'metadata' => 'array',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    public function isEnabled(): bool
    {
        return $this->status === 'enabled';
    }
}
