<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Services\Backups\GoogleDriveBackupService;
use Throwable;

class BackupGoogleController extends Controller
{
    public function connect(Request $request, BackupDestination $destination): RedirectResponse
    {
        Gate::authorize('backups.configure');
        if ($destination->protocol !== 'google' || ! $destination->google_client_id || ! $destination->google_client_secret) {
            return redirect()->route('admin.backups.index')->with('cms_backups_error', 'Guarde primeiro o ID e o segredo do cliente Google.');
        }
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('cms-backup-google.'.$destination->id, [
            'state' => $state, 'verifier' => $verifier, 'user' => $request->user()->getAuthIdentifier(),
            'expires' => time() + 600, 'credentials' => $this->fingerprint($destination),
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $destination->google_client_id, 'redirect_uri' => route('admin.backups.google.callback', $destination),
            'response_type' => 'code', 'scope' => GoogleDriveBackupService::SCOPE, 'access_type' => 'offline',
            'prompt' => 'consent', 'state' => $state, 'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request, BackupDestination $destination, GoogleDriveBackupService $google): RedirectResponse
    {
        Gate::authorize('backups.configure');
        $saved = $request->session()->pull('cms-backup-google.'.$destination->id);
        $state = $request->query('state');
        abort_unless(is_array($saved) && ($saved['user'] ?? null) === $request->user()->getAuthIdentifier()
            && ($saved['expires'] ?? 0) >= time() && is_string($state) && hash_equals($saved['state'], $state)
            && hash_equals($saved['credentials'], $this->fingerprint($destination)) && $destination->protocol === 'google', 403);
        try {
            if ($request->has('error') || ! is_string($request->query('code')) || $request->query('code') === '') {
                return redirect()->route('admin.backups.index')->with('cms_backups_error', 'A autorização Google foi cancelada. Nenhuma ligação foi alterada.');
            }
            // Só persistir a nova ligação quando os tokens e a pasta estiverem confirmados.
            $destination->google_tokens = $google->exchange($destination, $request->query('code'), $saved['verifier']);
            if (! $destination->google_folder_id) {
                $destination->google_folder_id = $google->createFolder($destination);
            }
            $destination->forceFill(['connection_status' => 'connected', 'last_tested_at' => now(), 'last_error' => null])->save();
            $this->audit($request, 'backups.google.connected');

            return redirect()->route('admin.backups.index')->with('cms_backups_success', 'Google Drive ligado. Os backups serão guardados na pasta criada pelo CMS.');
        } catch (Throwable) {
            // Não registar respostas OAuth, códigos ou segredos em logs.
            return redirect()->route('admin.backups.index')->with('cms_backups_error', 'Não foi possível ligar o Google Drive. Confirme as credenciais, o endereço de retorno e as autorizações.');
        }
    }

    public function disconnect(Request $request, BackupDestination $destination): RedirectResponse
    {
        Gate::authorize('backups.configure');
        $destination->forceFill(['google_tokens' => null, 'google_folder_id' => null, 'connection_status' => 'not_tested'])->save();
        $destination->plans()->whereIn('storage_mode', ['remote', 'local_and_remote'])->update(['enabled' => false]);
        $request->session()->forget('cms-backup-google.'.$destination->id);
        $this->audit($request, 'backups.google.disconnected');

        return redirect()->route('admin.backups.index')->with('cms_backups_success', 'Ligação Google removida deste destino. Pode também revogar o acesso na sua conta Google.');
    }

    private function fingerprint(BackupDestination $destination): string
    {
        return hash('sha256', $destination->google_client_id.'|'.$destination->google_client_secret);
    }

    private function audit(Request $request, string $action): void
    {
        BackupAuditLog::create(['action' => $action, 'user_id' => $request->user()->getAuthIdentifier(), 'result' => 'success']);
    }
}
