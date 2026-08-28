<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentScanItem extends Model
{
    protected $table = 'cms_consent_scan_items';

    protected $fillable = ['scan_id', 'service_id', 'type', 'identifier', 'domain', 'url', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(ConsentScan::class, 'scan_id');
    }
}
