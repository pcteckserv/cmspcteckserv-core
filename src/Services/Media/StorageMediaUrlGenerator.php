<?php

namespace Pcteckserv\CmsCore\Services\Media;

use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Contracts\MediaUrlGenerator;
use Pcteckserv\CmsCore\Models\Media;

class StorageMediaUrlGenerator implements MediaUrlGenerator
{
    public function url(Media $media, ?string $variant = null): string
    {
        $disk = Storage::disk($media->disk);
        $path = match ($variant) {
            'thumbnail' => $media->thumbnail_path ?: $media->path,
            'optimized', 'webp' => $media->optimized_path ?: $media->path,
            default => $media->variants[$variant] ?? $media->path,
        };

        if ($path && $disk->exists($path)) {
            return $disk->url($path);
        }

        if ($media->path && $disk->exists($media->path)) {
            return $disk->url($media->path);
        }

        if ($media->media_type === 'image') {
            return asset(config('cms-core.assets.placeholder_image', 'vendor/cms-core/images/placeholder.webp'));
        }

        return $disk->url($media->path);
    }
}
