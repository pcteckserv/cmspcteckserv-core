<?php

namespace Pcteckserv\CmsCore\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'title',
        'description',
        'canonical_url',
        'robots_index',
        'robots_follow',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_card',
        'schema_type',
        'schema_data',
        'exclude_from_sitemap',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'schema_data' => 'array',
        'exclude_from_sitemap' => 'boolean',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
