<?php

namespace Pcteckserv\CmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentCategory extends Model
{
    protected $table = 'cms_consent_categories';

    protected $fillable = ['key', 'name', 'description', 'public_text', 'sort_order', 'is_active', 'is_required', 'color', 'icon'];

    protected $casts = ['is_active' => 'boolean', 'is_required' => 'boolean'];

    public function services(): HasMany
    {
        return $this->hasMany(ConsentService::class, 'category_id');
    }
}
