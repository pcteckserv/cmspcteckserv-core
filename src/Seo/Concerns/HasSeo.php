<?php

namespace Pcteckserv\CmsCore\Seo\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Pcteckserv\CmsCore\Seo\Models\SeoMeta;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
