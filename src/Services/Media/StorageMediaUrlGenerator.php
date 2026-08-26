<?php

namespace Pcteckserv\CmsCore\Services\Media;

use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Contracts\MediaUrlGenerator;
use Pcteckserv\CmsCore\Models\Media;

class StorageMediaUrlGenerator implements MediaUrlGenerator
{
    public function url(Media $media, ?string $variant = null): string
    {
        $path = match ($variant) {
            'thumbnail' => $media->thumbnail_path ?: $media->path,
            'optimized', 'webp' => $media->optimized_path ?: $media->path,
            default => $media->variants[$variant] ?? $media->path,
        };

        return Storage::disk($media->disk)->url($path);
    }
}
