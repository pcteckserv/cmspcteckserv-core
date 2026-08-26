<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;

class BackupAuditLog extends Model
{
    protected $table = 'cms_backup_audit_logs';

    protected $fillable = ['user_id', 'backup_run_id', 'action', 'result', 'context'];

    protected $casts = [
        'context' => 'array',
    ];
}
