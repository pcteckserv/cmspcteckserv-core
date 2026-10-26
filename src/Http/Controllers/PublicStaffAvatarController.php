<?php

namespace Pcteckserv\CmsCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\UserProfile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStaffAvatarController
{
    public function __invoke(Request $request, string $filename): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)$/i', $filename), 404);
        $relative = 'staff-avatars/'.$filename;
        abort_unless(UserProfile::where('avatar_path', $relative)->exists(), 404);
        $config = config('filesystems.disks.public', []);
        abort_unless(($config['driver'] ?? null) === 'local' && ($config['visibility'] ?? null) === 'public', 404);
        $disk = Storage::disk('public');
        $diskRoot = realpath($disk->path(''));
        $root = realpath($disk->path('staff-avatars'));
        $absolute = realpath($disk->path($relative));
        abort_unless($diskRoot !== false && $root !== false && str_starts_with($root, rtrim($diskRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) && $absolute !== false && is_file($absolute)
            && str_starts_with($absolute, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        $response = response()->file($absolute, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ])->setPublic()->setMaxAge(3600)->setAutoEtag()->setAutoLastModified();
        $response->isNotModified($request);

        return $response;
    }
}
