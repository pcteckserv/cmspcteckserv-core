<?php

namespace Pcteckserv\CmsCore\Seo\Events;

class SitemapGenerated
{
    public function __construct(public readonly int $urlCount)
    {
    }
}
