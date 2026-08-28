<?php

namespace Pcteckserv\CmsCore\Seo\Events;

class RedirectDeleted
{
    public function __construct(public readonly string $source)
    {
    }
}
