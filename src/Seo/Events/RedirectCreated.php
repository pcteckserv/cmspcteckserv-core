<?php

namespace Pcteckserv\CmsCore\Seo\Events;

use Pcteckserv\CmsCore\Seo\Models\SeoRedirect;

class RedirectCreated
{
    public function __construct(public readonly SeoRedirect $redirect)
    {
    }
}
