<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'cms_activity_logs';

    protected $fillable = [
        'user_type',
        'user_id',
        'action',
        'category',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'url',
        'http_method',
        'properties',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
