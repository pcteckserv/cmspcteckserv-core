<?php

namespace Pcteckserv\CmsCore\Events;

use Pcteckserv\CmsCore\Models\Media;

class MediaDeleted
{
    public function __construct(public readonly Media $media)
    {
    }
}
