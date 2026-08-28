<?php

namespace Pcteckserv\CmsCore\Seo\Contracts;

use Pcteckserv\CmsCore\Seo\DTOs\SitemapUrl;

interface SitemapProviderInterface
{
    /** @return iterable<SitemapUrl> */
    public function urls(): iterable;
}
