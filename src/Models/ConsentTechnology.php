<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentTechnology extends Model
{
    protected $table = 'cms_consent_technologies';

    protected $fillable = ['service_id', 'type', 'name', 'domain', 'path', 'duration', 'is_third_party', 'value', 'found_on_urls'];

    protected $casts = ['is_third_party' => 'boolean', 'found_on_urls' => 'array'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ConsentService::class, 'service_id');
    }
}
