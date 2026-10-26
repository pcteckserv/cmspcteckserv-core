<?php

namespace Pcteckserv\CmsCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        abort_if(str_contains($path, '\\') || str_contains($path, "\0") || in_array('..', explode('/', $path), true), 404);
        abort_unless(preg_match('/^([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})(?:-\d+)?\.[a-z0-9]+$/i', basename($path), $match), 404);
        $media = Media::query()->where('uuid', $match[1])->firstOrFail();
        $config = config('filesystems.disks.'.$media->disk, []);
        abort_unless(($config['driver'] ?? null) === 'local' && ($config['visibility'] ?? null) === 'public', 404);

        $relative = trim((string) config('cms-core.media.directory', 'cms/media'), '/').'/'.$path;
        $allowed = array_filter([$media->path, $media->optimized_path, $media->thumbnail_path, ...array_values($media->variants ?? [])]);
        abort_unless(in_array($relative, $allowed, true), 404);
        $disk = Storage::disk($media->disk);
        $root = realpath($disk->path(''));
        $absolute = realpath($disk->path($relative));
        abort_unless($root !== false && $absolute !== false && is_file($absolute)
            && str_starts_with($absolute, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), 404);

        $response = response()->file($absolute, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ])->setPublic()->setMaxAge(3600)->setAutoEtag()->setAutoLastModified();
        $response->isNotModified($request);

        return $response;
    }
}
