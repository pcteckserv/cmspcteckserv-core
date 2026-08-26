<?php

namespace Pcteckserv\CmsCore\Services\Media;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Events\MediaOptimized;
use Pcteckserv\CmsCore\Models\Media;

class MediaOptimizer
{
    public function optimize(Media $media): Media
    {
        if (! config('cms-core.media.optimization.enabled', true) || $media->media_type !== 'image') {
            return $media;
        }

        $media->forceFill(['optimization_status' => Media::STATUS_PROCESSING])->save();

        try {
            $disk = Storage::disk($media->disk);
            $absolutePath = $disk->path($media->path);

            if (! function_exists('imagewebp') || ! is_file($absolutePath)) {
                $media->forceFill(['optimization_status' => Media::STATUS_OPTIMIZED])->save();

                return $media;
            }

            $image = $this->createImage($absolutePath, $media->mime_type);

            if (! $image) {
                $media->forceFill(['optimization_status' => Media::STATUS_OPTIMIZED])->save();

                return $media;
            }

            $quality = (int) config('cms-core.media.optimization.quality', 82);
            $baseDirectory = trim($media->directory, '/');
            $name = pathinfo($media->filename, PATHINFO_FILENAME);
            $optimizedPath = "{$baseDirectory}/optimized/{$name}.webp";
            $thumbnailPath = "{$baseDirectory}/thumbnails/{$name}.webp";

            $this->storeWebp($disk->path($optimizedPath), $image, $quality);
            $thumbnail = $this->resize($image, $media->width ?: imagesx($image), $media->height ?: imagesy($image), 300);
            $this->storeWebp($disk->path($thumbnailPath), $thumbnail, $quality);

            $variants = [];
            foreach (config('cms-core.media.optimization.variants', []) as $width) {
                $width = (int) $width;
                if ($width <= 0 || ($media->width && $width >= $media->width)) {
                    continue;
                }

                $variantPath = "{$baseDirectory}/variants/{$name}-{$width}.webp";
                $variant = $this->resize($image, $media->width ?: imagesx($image), $media->height ?: imagesy($image), $width);
                $this->storeWebp($disk->path($variantPath), $variant, $quality);
                $variants[(string) $width] = $variantPath;
            }

            $media->forceFill([
                'optimized_path' => $optimizedPath,
                'thumbnail_path' => $thumbnailPath,
                'optimized_size' => $disk->size($optimizedPath),
                'variants' => $variants,
                'optimization_status' => Media::STATUS_OPTIMIZED,
            ])->save();

            event(new MediaOptimized($media));
        } catch (\Throwable $exception) {
            Log::warning('Falha ao otimizar media.', [
                'media_uuid' => $media->uuid,
                'message' => $exception->getMessage(),
            ]);

            $media->forceFill([
                'optimization_status' => Media::STATUS_FAILED,
                'metadata' => array_merge($media->metadata ?? [], ['optimization_error' => 'Não foi possível otimizar o ficheiro.']),
            ])->save();
        }

        return $media->fresh();
    }

    private function createImage(string $path, string $mimeType): mixed
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
            default => false,
        };
    }

    private function resize(mixed $image, int $sourceWidth, int $sourceHeight, int $targetWidth): mixed
    {
        $targetWidth = min($targetWidth, $sourceWidth);
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $resized;
    }

    private function storeWebp(string $path, mixed $image, int $quality): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        imagewebp($image, $path, $quality);
    }
}
