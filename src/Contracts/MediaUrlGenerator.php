<?php

namespace Pcteckserv\CmsCore\Contracts;

use Pcteckserv\CmsCore\Models\Media;

interface MediaUrlGenerator
{
    public function url(Media $media, ?string $variant = null): string;
}
