<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentService extends Model
{
    protected $table = 'cms_consent_services';

    protected $fillable = [
        'category_id', 'key', 'name', 'provider', 'description', 'purpose', 'status',
        'requires_consent', 'source', 'confidence', 'review_status', 'found_on_urls', 'detection_reason',
    ];

    protected $casts = ['requires_consent' => 'boolean', 'found_on_urls' => 'array'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ConsentCategory::class, 'category_id');
    }

    public function technologies(): HasMany
    {
        return $this->hasMany(ConsentTechnology::class, 'service_id');
    }
}
