<?php

namespace Pcteckserv\CmsCore\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAudit extends Model
{
    protected $fillable = ['url', 'status_code', 'score', 'results', 'scanned_at'];

    protected $casts = [
        'status_code' => 'integer',
        'score' => 'integer',
        'results' => 'array',
        'scanned_at' => 'datetime',
    ];
}
