<?php

namespace Pcteckserv\CmsCore\Seo\Events;

use Pcteckserv\CmsCore\Seo\Models\SeoMeta;

class SeoMetaUpdated
{
    public function __construct(public readonly SeoMeta $seoMeta)
    {
    }
}
