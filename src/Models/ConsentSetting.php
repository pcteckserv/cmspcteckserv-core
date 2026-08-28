<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentSetting extends Model
{
    protected $table = 'cms_consent_settings';

    protected $fillable = ['version', 'banner_enabled', 'server_records_enabled', 'texts', 'published_config', 'published_at'];

    protected $casts = [
        'banner_enabled' => 'boolean',
        'server_records_enabled' => 'boolean',
        'texts' => 'array',
        'published_config' => 'array',
        'published_at' => 'datetime',
    ];
}
