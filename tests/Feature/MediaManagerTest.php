<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\Media\MediaService;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_consegue_ver_biblioteca_de_media(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Media');
    }

    public function test_utilizador_sem_permissao_nao_consegue_ver_media(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.media.index'))
            ->assertForbidden();
    }

    public function test_upload_valido_cria_registo_com_nome_seguro_e_url_centralizado(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->create('catalogo.pdf', 120, 'application/pdf')],
            ])
            ->assertCreated()
            ->assertJsonPath('items.0.name', 'catalogo.pdf');

        $media = Media::query()->firstOrFail();

        $this->assertSame('catalogo.pdf', $media->original_filename);
        $this->assertNotSame('catalogo.pdf', $media->filename);
        $this->assertSame('document', $media->media_type);
        $this->assertSame(hash_file('sha256', Storage::disk('public')->path($media->path)), $media->checksum);
        $this->assertStringContainsString($media->path, app(MediaService::class)->url($media));
    }

    public function test_upload_php_e_rejeitado(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->create('shell.php', 1, 'application/x-php')],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('cms_media', 0);
    }

    public function test_svg_malicioso_e_rejeitado_mesmo_quando_svg_esta_activo(): void
    {
        Storage::fake('public');
        config(['cms-core.media.allow_svg' => true]);

        $svg = UploadedFile::fake()->createWithContent(
            'icone.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
        );

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.media.store'), ['files' => [$svg]])
            ->assertUnprocessable();

        $this->assertDatabaseCount('cms_media', 0);
    }

    public function test_soft_delete_restore_e_force_delete(): void
    {
        Storage::fake('public');
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->create('manual.pdf', 80, 'application/pdf')],
            ])
            ->assertCreated();

        $media = Media::query()->firstOrFail();
        $path = $media->path;

        $this->actingAs($admin)->delete(route('admin.media.destroy', $media))->assertRedirect();
        $this->assertSoftDeleted('cms_media', ['id' => $media->id]);

        $this->actingAs($admin)->post(route('admin.media.restore', $media->id))->assertRedirect();
        $this->assertNotSoftDeleted('cms_media', ['id' => $media->id]);

        $media->delete();
        $this->actingAs($admin)->delete(route('admin.media.force-delete', $media->id))->assertRedirect();
        $this->assertDatabaseMissing('cms_media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_url_de_thumbnail_faz_fallback_para_original_quando_ficheiro_nao_existe(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/media/original.png', 'conteudo');

        $media = Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'directory' => 'cms/media',
            'filename' => 'original.png',
            'path' => 'cms/media/original.png',
            'thumbnail_path' => 'cms/media/thumbnails/inexistente.webp',
            'original_filename' => 'original.png',
            'extension' => 'png',
            'mime_type' => 'image/png',
            'media_type' => 'image',
            'size' => 8,
            'checksum' => hash('sha256', 'conteudo'),
            'optimization_status' => Media::STATUS_FAILED,
        ]);

        $url = app(MediaService::class)->url($media, 'thumbnail');

        $this->assertStringContainsString('cms/media/original.png', $url);
    }

    public function test_api_de_media_devolve_url_otimizado_para_imagens(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/media/original.png', 'original');
        Storage::disk('public')->put('cms/media/optimized/original.webp', 'webp');

        $media = Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'directory' => 'cms/media',
            'filename' => 'original.png',
            'path' => 'cms/media/original.png',
            'optimized_path' => 'cms/media/optimized/original.webp',
            'original_filename' => 'original.png',
            'extension' => 'png',
            'mime_type' => 'image/png',
            'media_type' => 'image',
            'size' => 8,
            'checksum' => hash('sha256', 'original'),
            'optimization_status' => Media::STATUS_OPTIMIZED,
        ]);

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.media.show', $media))
            ->assertOk()
            ->assertJsonPath('url', Storage::disk('public')->url('cms/media/optimized/original.webp'))
            ->assertJsonPath('original_url', Storage::disk('public')->url('cms/media/original.png'));
    }

    private function superAdmin(): User
    {
        app(PermissionSynchronizer::class)->sync();

        $role = Role::query()->firstOrCreate(
            ['key' => 'core.super_admin'],
            ['name' => 'Super Admin', 'is_protected' => true],
        );

        $user = User::factory()->create();
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }
}
