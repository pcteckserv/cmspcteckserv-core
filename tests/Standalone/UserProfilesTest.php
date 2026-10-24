<?php

namespace Pcteckserv\CmsCore\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\UserProfile;

require_once __DIR__.'/SharedCustomersTest.php';

class UserProfilesTest extends SharedCustomersTest
{
    public function test_staff_avatar_and_biography_are_owned_validated_replaced_and_removed(): void
    {
        Storage::fake('public');
        $staff = $this->manager();
        $this->actingAs($staff, 'web');
        $data = ['name' => $staff->name, 'email' => $staff->email, 'current_password' => 'wrong', 'biography' => 'Especialista em cortes.'];
        $this->put('/admin/perfil', $data)->assertSessionHasErrors('current_password');
        $this->assertSame(0, UserProfile::count());
        $data['current_password'] = 'password-test-123';
        $data['avatar'] = UploadedFile::fake()->createWithContent('avatar.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j2ioAAAAASUVORK5CYII='));
        $data['user_id'] = 999;
        $this->put('/admin/perfil', $data)->assertSessionHasNoErrors();
        $profile = UserProfile::where('user_id', $staff->id)->firstOrFail();
        $this->assertSame($data['biography'], $profile->biography);
        Storage::disk('public')->assertExists($profile->avatar_path);
        $old = $profile->avatar_path;
        $this->get('/admin/perfil')->assertOk()->assertSee('Especialista em cortes.')->assertSee($profile->avatarUrl());
        $data['avatar'] = UploadedFile::fake()->createWithContent('unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->put('/admin/perfil', $data)->assertSessionHasErrors('avatar');
        $this->assertSame($old, $profile->fresh()->avatar_path);
        unset($data['avatar']);
        $data['remove_avatar'] = 1;
        $this->put('/admin/perfil', $data)->assertSessionHasNoErrors();
        $this->assertNull($profile->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($old);
    }
}
