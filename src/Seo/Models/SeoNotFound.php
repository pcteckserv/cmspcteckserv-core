<?php

namespace Pcteckserv\CmsCore\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class SeoNotFound extends Model
{
    protected $table = 'seo_not_found_errors';

    protected $fillable = [
        'url',
        'method',
        'referer',
        'user_agent',
        'ip_hash',
        'hits',
        'first_seen_at',
        'last_seen_at',
        'is_ignored',
        'is_resolved',
    ];

    protected $casts = [
        'hits' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_ignored' => 'boolean',
        'is_resolved' => 'boolean',
    ];
}
