<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentScan extends Model
{
    protected $table = 'cms_consent_scans';

    protected $fillable = ['started_at', 'finished_at', 'status', 'pages_scanned', 'services_found', 'technologies_found', 'changes_found', 'urls', 'summary', 'error_log'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime', 'urls' => 'array', 'summary' => 'array'];

    public function items(): HasMany
    {
        return $this->hasMany(ConsentScanItem::class, 'scan_id');
    }
}
