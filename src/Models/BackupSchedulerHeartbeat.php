<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSchedulerHeartbeat extends Model
{
    protected $table = 'cms_backup_scheduler_heartbeats';

    protected $fillable = ['ran_at'];

    protected $casts = [
        'ran_at' => 'datetime',
    ];
}
