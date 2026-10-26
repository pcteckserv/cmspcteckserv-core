<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\UserProfile;
use Tests\TestCase;

class PublicStaffAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_avatar_is_public_without_a_storage_link_and_supports_conditional_cache(): void
    {
        config(['filesystems.disks.public.url' => 'https://example.test/cms/storage']);
        Storage::fake('public', ['url' => 'https://example.test/cms/storage']);
        Storage::disk('public')->put('staff-avatars/existing.jpg', UploadedFile::fake()->image('foto.jpg', 80, 120)->get());
        UserProfile::create(['user_id' => 1, 'avatar_path' => 'staff-avatars/existing.jpg']);
        $response = $this->get('/storage/staff-avatars/existing.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/storage/staff-avatars/existing.jpg', ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
        config(['filesystems.disks.public.url' => 'https://example.test/cms/storage']);
        $this->assertSame('https://example.test/cms/storage/staff-avatars/existing.jpg', UserProfile::sole()->avatarUrl());
    }

    public function test_unregistered_missing_private_and_non_image_files_are_rejected(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('staff-avatars/secret.jpg', '<?php echo "private";');
        $this->get('/storage/staff-avatars/secret.jpg')->assertNotFound();
        $profile = UserProfile::create(['user_id' => 1, 'avatar_path' => 'staff-avatars/secret.jpg']);
        $this->get('/storage/staff-avatars/secret.jpg')->assertNotFound();
        Storage::disk('public')->put('staff-avatars/secret.jpg', UploadedFile::fake()->image('foto.jpg')->get());
        config(['filesystems.disks.public.visibility' => 'private']);
        $this->get('/storage/staff-avatars/secret.jpg')->assertNotFound();
        config(['filesystems.disks.public.visibility' => 'public']);
        Storage::disk('public')->delete('staff-avatars/secret.jpg');
        $this->get('/storage/staff-avatars/secret.jpg')->assertNotFound();
        $profile->delete();
        $this->assertContains($this->get('/storage/staff-avatars/../logs/laravel.log')->status(), [403, 404]);
        $this->get('/storage/staff-avatars/secret.php')->assertNotFound();
    }
}
