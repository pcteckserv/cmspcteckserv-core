<?php

namespace Pcteckserv\CmsCore\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $fillable = ['source', 'destination', 'status_code', 'is_active', 'hits', 'last_hit_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];
}
