<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use Pcteckserv\CmsCore\Support\SiteOptions;
use Tests\TestCase;

class SeoSettingsMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_use_image_pickers_and_preserve_existing_urls(): void
    {
        $this->authorizeSettings();
        app(SiteOptions::class)->setMany(['seo_default_og_image' => 'https://example.test/existing.jpg']);

        $this->get(route('admin.seo.settings.edit'))
            ->assertOk()
            ->assertSee('data-cms-media-picker-target-url="#seo_default_og_image"', false)
            ->assertSee('data-cms-media-picker-target-url="#seo_organization_logo"', false)
            ->assertSee('https://example.test/existing.jpg');
    }

    public function test_selected_images_are_resolved_on_the_server(): void
    {
        $this->authorizeSettings();
        Storage::fake('public');
        Storage::disk('public')->put('seo/image.webp', 'image');
        $media = $this->media();

        $this->put(route('admin.seo.settings.update'), $this->payload() + [
            'seo_default_og_image_media_id' => $media->id,
            'seo_organization_logo_media_id' => $media->id,
            'seo_default_og_image' => 'https://example.test/manipulated.jpg',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $options = app(SiteOptions::class);
        $url = Storage::disk('public')->url('seo/image.webp');
        $this->assertSame($url, $options->get('seo_default_og_image'));
        $this->assertSame($url, $options->get('seo_organization_logo'));
        $this->assertDatabaseMissing('cms_site_options', ['key' => 'seo_default_og_image_media_id']);
    }

    public function test_invalid_missing_document_and_deleted_media_are_rejected(): void
    {
        $this->authorizeSettings();
        $document = $this->media(['media_type' => 'document', 'path' => 'seo/document.pdf']);
        $deleted = $this->media();
        $deleted->delete();

        foreach (['invalid', 999999, $document->id, $deleted->id] as $id) {
            $this->put(route('admin.seo.settings.update'), $this->payload() + [
                'seo_default_og_image_media_id' => $id,
                'seo_organization_logo_media_id' => $id,
            ])->assertSessionHasErrors(['seo_default_og_image_media_id', 'seo_organization_logo_media_id']);
        }
    }

    public function test_image_urls_can_be_cleared_and_legacy_urls_still_work(): void
    {
        $this->authorizeSettings();

        $this->put(route('admin.seo.settings.update'), $this->payload() + [
            'seo_default_og_image' => 'https://example.test/legacy.jpg',
            'seo_organization_logo' => 'https://example.test/logo.jpg',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://example.test/legacy.jpg', app(SiteOptions::class)->get('seo_default_og_image'));

        $this->put(route('admin.seo.settings.update'), $this->payload() + [
            'seo_default_og_image_media_id' => '',
            'seo_organization_logo_media_id' => '',
            'seo_default_og_image' => '',
            'seo_organization_logo' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cms_site_options', ['key' => 'seo_default_og_image', 'value' => null]);
        $this->assertDatabaseHas('cms_site_options', ['key' => 'seo_organization_logo', 'value' => null]);
    }

    public function test_media_permission_is_required_when_submitting_a_media_id(): void
    {
        $this->authorizeSettings(false);
        $media = $this->media();

        $this->put(route('admin.seo.settings.update'), $this->payload() + [
            'seo_default_og_image_media_id' => $media->id,
        ])->assertForbidden();
    }

    public function test_guests_and_users_without_permission_cannot_change_settings(): void
    {
        $this->put(route('admin.seo.settings.update'), $this->payload())->assertRedirect();
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.seo.settings.edit'))->assertForbidden();
        $this->put(route('admin.seo.settings.update'), $this->payload())->assertForbidden();
    }

    private function authorizeSettings(bool $mediaAccess = true): void
    {
        app(PermissionSynchronizer::class)->sync();
        $role = Role::query()->create(['key' => 'seo-test', 'name' => 'Gestor SEO']);
        $permissions = ['seo.settings.manage'];
        if ($mediaAccess) {
            $permissions[] = 'media.view';
        }
        $role->permissions()->sync(Permission::query()->whereIn('key', $permissions)->pluck('id'));
        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);
        $this->actingAs($user);
    }

    private function payload(): array
    {
        return [
            'seo_site_name' => 'Site de teste',
            'seo_default_title' => 'Título de teste',
            'seo_title_template' => '{page_title} | {site_name}',
            'seo_base_url' => 'https://example.test',
            'seo_twitter_card' => 'summary_large_image',
            'seo_organization_type' => 'Organization',
        ];
    }

    private function media(array $attributes = []): Media
    {
        return Media::query()->create(array_replace([
            'disk' => 'public',
            'directory' => 'seo',
            'filename' => 'image.webp',
            'path' => 'seo/image.webp',
            'original_filename' => 'imagem.webp',
            'extension' => 'webp',
            'mime_type' => 'image/webp',
            'media_type' => 'image',
            'size' => 5,
            'checksum' => hash('sha256', 'image'),
        ], $attributes));
    }
}
