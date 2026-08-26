<?php

namespace Pcteckserv\CmsCore\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Contracts\MediaUrlGenerator;
use Pcteckserv\CmsCore\Events\MediaUploaded;
use Pcteckserv\CmsCore\Models\Media;

class MediaService
{
    public function __construct(
        private readonly MediaValidator $validator,
        private readonly MediaOptimizer $optimizer,
        private readonly MediaUrlGenerator $urlGenerator,
    ) {
    }

    public function upload(UploadedFile $file, ?int $userId = null, ?int $collectionId = null, bool $allowSvgUpload = false): Media
    {
        $this->validator->validate($file, $allowSvgUpload);

        $checksum = hash_file('sha256', $file->getRealPath());
        $existing = Media::query()->where('checksum', $checksum)->first();

        if ($existing) {
            return $existing;
        }

        $disk = config('cms-core.media.disk', 'public');
        $directory = trim(config('cms-core.media.directory', 'cms/media'), '/').'/'.now()->format('Y/m');
        $extension = Str::lower($file->getClientOriginalExtension());
        $uuid = (string) Str::uuid();
        $filename = "{$uuid}.{$extension}";
        $path = $file->storeAs($directory, $filename, $disk);
        [$width, $height] = $this->dimensions($file);

        $media = Media::query()->create([
            'uuid' => $uuid,
            'collection_id' => $collectionId,
            'disk' => $disk,
            'directory' => $directory,
            'filename' => $filename,
            'path' => $path,
            'original_filename' => basename($file->getClientOriginalName()),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'media_type' => $this->mediaType($file->getMimeType()),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
            'uploaded_by' => $userId,
            'optimization_status' => Media::STATUS_PENDING,
            'original_size' => $file->getSize(),
            'metadata' => [
                'duplicate_of' => null,
            ],
        ]);

        event(new MediaUploaded($media));

        return $this->optimizer->optimize($media);
    }

    public function url(Media $media, ?string $variant = null): string
    {
        return $this->urlGenerator->url($media, $variant);
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    public function forceDelete(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $paths = array_filter([
            $media->path,
            $media->optimized_path,
            $media->thumbnail_path,
            ...array_values($media->variants ?? []),
        ]);

        $disk->delete($paths);
        $media->forceDelete();
    }

    private function dimensions(UploadedFile $file): array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/') || $file->getMimeType() === 'image/svg+xml') {
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath());

        return $size ? [$size[0], $size[1]] : [null, null];
    }

    private function mediaType(?string $mimeType): string
    {
        return str_starts_with((string) $mimeType, 'image/') ? 'image' : 'document';
    }
}
