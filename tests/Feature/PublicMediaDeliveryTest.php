<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\Media;
use Tests\TestCase;

class PublicMediaDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function image(): Media
    {
        Storage::fake('public');
        $uuid = (string) Str::uuid();
        $path = 'cms/media/2026/10/'.$uuid.'.jpeg';
        Storage::disk('public')->put($path, 'image fixture');

        return Media::query()->create([
            'uuid' => $uuid, 'disk' => 'public', 'directory' => 'cms/media/2026/10',
            'filename' => $uuid.'.jpeg', 'path' => $path, 'original_filename' => 'foto.jpeg',
            'extension' => 'jpeg', 'mime_type' => 'image/jpeg', 'media_type' => 'image',
            'size' => 13, 'checksum' => hash('sha256', $uuid),
        ]);
    }

    public function test_public_media_is_served_without_a_storage_link(): void
    {
        $image = $this->image();
        $response = $this->get('/storage/'.$image->path)->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(realpath(Storage::disk('public')->path($image->path)), $response->baseResponse->getFile()->getRealPath());
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->get('/storage/'.$image->path, ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
    }

    public function test_registered_optimized_thumbnail_and_size_variants_are_served(): void
    {
        $image = $this->image();
        $paths = [
            'optimized_path' => $image->directory.'/optimized/'.$image->uuid.'.webp',
            'thumbnail_path' => $image->directory.'/thumbnails/'.$image->uuid.'.webp',
        ];
        $variant = $image->directory.'/variants/'.$image->uuid.'-600.webp';
        $image->update([...$paths, 'variants' => ['600' => $variant]]);
        foreach ([...array_values($paths), $variant] as $path) {
            Storage::disk('public')->put($path, 'variant fixture');
            $this->get('/storage/'.$path)->assertOk();
        }
    }

    public function test_private_deleted_missing_and_unregistered_files_are_not_exposed(): void
    {
        $image = $this->image();
        $unregistered = $image->directory.'/optimized/'.$image->uuid.'.webp';
        Storage::disk('public')->put($unregistered, 'not registered');
        $this->get('/storage/'.$unregistered)->assertNotFound();
        $this->get('/storage/cms/media/.env')->assertNotFound();
        $this->get('/storage/cms/media/../../logs/laravel.log')->assertNotFound();
        $image->update(['disk' => 'local']);
        $this->get('/storage/'.$image->path)->assertNotFound();
        $image->update(['disk' => 'public']);
        Storage::disk('public')->delete($image->path);
        $this->get('/storage/'.$image->path)->assertNotFound();
        Storage::disk('public')->put($image->path, 'image fixture');
        $image->delete();
        $this->get('/storage/'.$image->path)->assertNotFound();
    }

    public function test_media_delivery_preserves_the_subdirectory_url(): void
    {
        $image = $this->image();
        $server = ['SCRIPT_NAME' => '/cms/index.php', 'SCRIPT_FILENAME' => '/var/www/cms/index.php', 'PHP_SELF' => '/cms/index.php'];
        $this->call('GET', '/cms/storage/'.$image->path, [], [], [], $server)->assertOk();
    }
}
