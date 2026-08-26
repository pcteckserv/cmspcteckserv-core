<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class BackupDestination extends Model
{
    protected $table = 'cms_backup_destinations';

    protected $fillable = [
        'name',
        'disk',
        'protocol',
        'host',
        'port',
        'username',
        'password',
        'remote_path',
        'timeout',
        'passive',
        'ssl',
        'verify_ssl',
        'ssh_fingerprint',
        'connection_status',
        'last_tested_at',
        'last_error',
    ];

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'passive' => 'boolean',
        'ssl' => 'boolean',
        'verify_ssl' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function plans(): HasMany
    {
        return $this->hasMany(BackupPlan::class, 'destination_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(BackupRun::class, 'destination_id');
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPasswordPlainAttribute(): ?string
    {
        if (empty($this->attributes['password'])) {
            return null;
        }

        return Crypt::decryptString($this->attributes['password']);
    }
}
