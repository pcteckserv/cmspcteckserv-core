<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\Backups\GoogleDriveBackupService;
use Tests\TestCase;

class BackupGoogleTest extends TestCase
{
    use RefreshDatabase;

    private function destination(): BackupDestination
    {
        return BackupDestination::create(['name' => 'Google', 'protocol' => 'google', 'remote_path' => 'CMS backups', 'google_client_id' => 'test-client', 'google_client_secret' => 'test-secret']);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['key' => 'core.super_admin'], ['name' => 'Super Admin', 'is_protected' => true]);
        $user->cmsRoles()->sync([$role->id]);

        return $user;
    }

    public function test_google_connect_requires_configuration_permission_and_does_not_expose_secrets(): void
    {
        $destination = $this->destination();
        $this->post(route('admin.backups.google.connect', $destination))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('admin.backups.google.connect', $destination))->assertForbidden();
        $response = $this->actingAs($this->admin())->post(route('admin.backups.google.connect', $destination))->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/', $url);
        $this->assertStringNotContainsString('test-secret', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(GoogleDriveBackupService::SCOPE, $query['scope']);
        $this->assertStringNotContainsString('test-secret', json_encode($destination));
    }

    public function test_callback_rejects_unknown_expired_and_replayed_state(): void
    {
        Http::preventStrayRequests();
        $destination = $this->destination();
        $this->actingAs($this->admin());
        $callback = route('admin.backups.google.callback', $destination);
        $this->get($callback.'?state=forged&code=secret')->assertForbidden();
        $this->post(route('admin.backups.google.connect', $destination));
        $saved = session('cms-backup-google.'.$destination->id);
        $this->withSession(['cms-backup-google.'.$destination->id => array_replace($saved, ['expires' => time() - 1])]);
        $this->get($callback.'?state='.$saved['state'].'&code=secret')->assertForbidden();
        $this->get($callback.'?state='.$saved['state'].'&code=secret')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_callback_stores_encrypted_credentials_and_creates_a_dedicated_folder(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-test', 'refresh_token' => 'refresh-test', 'expires_in' => 3600, 'scope' => GoogleDriveBackupService::SCOPE]),
            'www.googleapis.com/drive/v3/files*' => Http::response(['id' => 'folder-test']),
        ]);
        $destination = $this->destination();
        $this->actingAs($this->admin())->post(route('admin.backups.google.connect', $destination));
        $state = session('cms-backup-google.'.$destination->id)['state'];
        $this->get(route('admin.backups.google.callback', $destination).'?state='.$state.'&code=test-code')->assertSessionHas('cms_backups_success');
        $destination->refresh();
        $this->assertSame('folder-test', $destination->google_folder_id);
        $this->assertSame('refresh-test', $destination->google_tokens['refresh_token']);
        $this->assertStringNotContainsString('refresh-test', $destination->getRawOriginal('google_tokens'));
        $this->assertStringNotContainsString('test-secret', $destination->getRawOriginal('google_client_secret'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/token') && isset($request['code_verifier']));
        $this->get(route('admin.backups.google.callback', $destination).'?state='.$state.'&code=test-code')->assertForbidden();
    }

    public function test_changed_credentials_cancel_pending_authorization_and_existing_tokens(): void
    {
        Http::preventStrayRequests();
        $destination = $this->destination();
        $destination->update(['google_tokens' => ['refresh_token' => 'old-refresh']]);
        $this->actingAs($this->admin())->post(route('admin.backups.google.connect', $destination));
        $state = session('cms-backup-google.'.$destination->id)['state'];
        $this->put(route('admin.backups.destinations.update', $destination), ['name' => 'Google', 'protocol' => 'google', 'google_client_id' => 'changed-client', 'google_client_secret' => '', 'remote_path' => 'Backups', 'timeout' => 30])->assertSessionHasNoErrors();
        $this->assertNull($destination->fresh()->google_tokens);
        $this->get(route('admin.backups.google.callback', $destination).'?state='.$state.'&code=test-code')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_resumable_upload_refreshes_the_token_and_only_deletes_cms_files(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'new-access', 'expires_in' => 3600]),
            'www.googleapis.com/upload/drive/v3/files?uploadType=resumable*' => Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/session-test']),
            'www.googleapis.com/upload/drive/v3/session-test' => Http::response(['id' => 'backup-test']),
            'www.googleapis.com/drive/v3/files/backup-test*' => Http::response(['appProperties' => ['cms_backup' => '1']]),
        ]);
        $destination = $this->destination();
        $destination->update(['google_tokens' => ['refresh_token' => 'refresh-test', 'expires_at' => 0]]);
        $file = tempnam(sys_get_temp_dir(), 'cms-test-');
        file_put_contents($file, 'a real backup');
        try {
            $service = app(GoogleDriveBackupService::class);
            $this->assertSame('drive/backup-test', $service->put($destination, $file, 'test.zip'));
            $service->delete($destination, 'drive/backup-test');
            Http::assertSent(fn ($request) => $request->method() === 'PUT' && $request->hasHeader('Authorization', 'Bearer new-access') && $request->body() === 'a real backup');
            Http::assertSent(fn ($request) => $request->method() === 'DELETE');
        } finally {
            @unlink($file);
        }
    }

    public function test_google_refuses_to_delete_unrelated_files(): void
    {
        Http::fake(['*' => Http::response(['appProperties' => []])]);
        $destination = $this->destination();
        $destination->update(['google_tokens' => ['access_token' => 'access', 'refresh_token' => 'refresh', 'expires_at' => time() + 3600]]);
        try {
            app(GoogleDriveBackupService::class)->delete($destination, 'drive/unrelated');
            $this->fail('Não devia eliminar ficheiros alheios.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('não foi criado', $exception->getMessage());
        }
        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_validation_does_not_flash_credentials_and_destination_selection_renders(): void
    {
        $destination = $this->destination();
        $this->actingAs($this->admin())->putJson(route('admin.backups.destinations.update', $destination), ['protocol' => 'invalid', 'google_client_secret' => 'do-not-flash'])->assertUnprocessable();
        $this->actingAs($this->admin())->put(route('admin.backups.destinations.update', $destination), ['protocol' => 'invalid', 'google_client_secret' => 'do-not-flash', 'password' => 'do-not-flash-either'])->assertSessionHasErrors();
        $this->assertArrayNotHasKey('google_client_secret', session('_old_input', []));
        $this->assertArrayNotHasKey('password', session('_old_input', []));
        $this->flushSession();
        $response = $this->get(route('admin.backups.index'))->assertOk()->assertSee('Google Drive')->assertSee('Backups a conservar')->assertSee('Apenas imagens');
        $response->assertDontSee('test-secret');
        if ($file = getenv('CMS_BACKUP_BROWSER_FIXTURE')) {
            file_put_contents($file, $response->getContent());
        }
    }

    public function test_google_cancellation_preserves_existing_authorization(): void
    {
        Http::preventStrayRequests();
        $destination = $this->destination();
        $destination->update(['google_tokens' => ['refresh_token' => 'existing']]);
        $this->actingAs($this->admin())->post(route('admin.backups.google.connect', $destination));
        $state = session('cms-backup-google.'.$destination->id)['state'];
        $this->get(route('admin.backups.google.callback', $destination).'?state='.$state.'&error=access_denied')->assertSessionHas('cms_backups_error');
        $this->assertSame('existing', $destination->fresh()->google_tokens['refresh_token']);
        Http::assertNothingSent();
    }

    public function test_manual_delete_removes_files_and_retains_an_audit_without_a_broken_foreign_key(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cms-backups/delete-test.zip', 'backup');
        $run = BackupRun::create(['type' => 'images', 'status' => 'success', 'local_path' => 'cms-backups/delete-test.zip']);
        $this->actingAs($this->admin())->delete(route('admin.backups.runs.destroy', $run))->assertSessionHas('cms_backups_success');
        Storage::disk('local')->assertMissing('cms-backups/delete-test.zip');
        $this->assertDatabaseMissing('cms_backup_runs', ['id' => $run->id]);
        $this->assertDatabaseHas('cms_backup_audit_logs', ['action' => 'backups.run.deleted', 'backup_run_id' => null]);
    }

    public function test_disconnecting_google_stops_historical_access_and_pauses_remote_schedules(): void
    {
        Http::preventStrayRequests();
        $destination = $this->destination();
        $destination->update(['google_tokens' => ['refresh_token' => 'current-authorization']]);
        $snapshot = $destination->snapshot();
        $this->assertArrayNotHasKey('google_tokens', $snapshot);
        $this->assertArrayNotHasKey('google_client_secret', $snapshot);
        $run = BackupRun::create(['destination_id' => $destination->id, 'type' => 'images', 'status' => 'success', 'remote_path' => 'drive/old-backup', 'destination_snapshot' => $snapshot]);
        $plan = BackupPlan::create(['name' => 'Google', 'enabled' => true, 'storage_mode' => 'remote', 'destination_id' => $destination->id]);
        $this->assertNotNull($run->storageDestination()->google_tokens);
        $this->actingAs($this->admin())->delete(route('admin.backups.google.disconnect', $destination))->assertSessionHas('cms_backups_success');
        $this->assertFalse($plan->fresh()->enabled);
        $this->assertNull($run->fresh()->storageDestination()->google_tokens);
        Http::assertNothingSent();
    }

    public function test_large_upload_checks_each_chunk_and_refuses_external_session_urls(): void
    {
        $destination = $this->destination();
        $destination->update(['google_tokens' => ['access_token' => 'access', 'refresh_token' => 'refresh', 'expires_at' => time() + 3600]]);
        Http::fake([
            'www.googleapis.com/upload/drive/v3/files*' => Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/session-test']),
            'www.googleapis.com/upload/drive/v3/session-test' => Http::sequence()->push([], 308, ['Range' => 'bytes=0-8388607'])->push(['id' => 'big-backup']),
        ]);
        $file = tempnam(sys_get_temp_dir(), 'cms-chunk-');
        file_put_contents($file, str_repeat('x', 8 * 1024 * 1024 + 100));
        try {
            $this->assertSame('drive/big-backup', app(GoogleDriveBackupService::class)->put($destination, $file, 'large.zip'));
            Http::assertSent(fn ($request) => $request->hasHeader('Content-Range', 'bytes 8388608-8388707/8388708'));
            Http::fake(['*' => Http::response([], 200, ['Location' => 'https://unrelated.example.test/upload/drive/'])]);
            $this->expectException(\RuntimeException::class);
            app(GoogleDriveBackupService::class)->put($destination, $file, 'large.zip');
        } finally {
            @unlink($file);
        }
    }
}
