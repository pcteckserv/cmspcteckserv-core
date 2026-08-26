<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupPlan extends Model
{
    protected $table = 'cms_backup_plans';

    protected $fillable = [
        'destination_id',
        'name',
        'enabled',
        'type',
        'frequency',
        'run_at',
        'weekdays',
        'month_day',
        'timezone',
        'compression',
        'included_paths',
        'excluded_paths',
        'storage_mode',
        'retention_days',
        'retention_count',
        'max_storage_bytes',
        'notification_emails',
        'notification_events',
        'alert_timing',
        'repeat_alert_after_minutes',
        'notify_recovery',
        'last_alert_sent_at',
        'last_alert_signature',
        'last_success_notified_at',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'weekdays' => 'array',
        'included_paths' => 'array',
        'excluded_paths' => 'array',
        'notification_emails' => 'array',
        'notification_events' => 'array',
        'notify_recovery' => 'boolean',
        'last_alert_sent_at' => 'datetime',
        'last_success_notified_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class, 'destination_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(BackupRun::class, 'plan_id');
    }
}
