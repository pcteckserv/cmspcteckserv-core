<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupRun extends Model
{
    protected $table = 'cms_backup_runs';

    protected $fillable = [
        'plan_id',
        'destination_id',
        'user_id',
        'type',
        'origin',
        'status',
        'storage_mode',
        'filename',
        'local_path',
        'remote_path',
        'size_before_compression',
        'size_bytes',
        'checksum_sha256',
        'attempts',
        'started_at',
        'finished_at',
        'duration_seconds',
        'protected',
        'failure_reason',
        'manifest',
    ];

    protected $casts = [
        'manifest' => 'array',
        'protected' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BackupPlan::class, 'plan_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(BackupDestination::class, 'destination_id');
    }
}
