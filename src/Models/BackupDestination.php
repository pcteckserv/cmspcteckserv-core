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
        'google_client_id', 'google_client_secret', 'google_tokens', 'google_folder_id',
    ];

    protected $casts = [
        'google_client_secret' => 'encrypted',
        'google_tokens' => 'encrypted:array',
        'port' => 'integer',
        'timeout' => 'integer',
        'passive' => 'boolean',
        'ssl' => 'boolean',
        'verify_ssl' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = ['password', 'google_client_secret', 'google_tokens'];

    public function googleOAuthCredentials(): array
    {
        // Não misturar um ID próprio com o segredo de outra aplicação.
        if ($this->google_client_id || $this->google_client_secret) {
            return ['client_id' => $this->google_client_id, 'client_secret' => $this->google_client_secret];
        }

        return config('cms-backups.google_oauth', []);
    }

    public function snapshot(): array
    {
        $values = $this->only(['disk', 'protocol', 'host', 'port', 'username', 'remote_path', 'timeout', 'passive', 'ssl', 'verify_ssl', 'ssh_fingerprint', 'google_client_id', 'google_folder_id']);
        if (in_array($this->protocol, ['ftp', 'ftps'], true)) {
            $values['password'] = $this->password_plain;
        }
        // O Google usa a autorização atual do destino, para que desligar a conta
        // interrompa também o acesso aos backups históricos.

        return $values;
    }

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
