<?php

namespace Pcteckserv\CmsCore\Events;

use Pcteckserv\CmsCore\Models\Media;

class MediaUploaded
{
    public function __construct(public readonly Media $media)
    {
    }
}
