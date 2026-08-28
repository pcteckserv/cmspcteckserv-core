<?php

namespace Pcteckserv\CmsCore\Seo\Events;

class SeoSettingsUpdated
{
    public function __construct(public readonly array $settings)
    {
    }
}
