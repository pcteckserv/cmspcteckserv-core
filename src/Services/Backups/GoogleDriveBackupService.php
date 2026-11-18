<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;

class GoogleDriveBackupService
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    private const API = 'https://www.googleapis.com/drive/v3/files';

    public function exchange(BackupDestination $destination, string $code, string $verifier): array
    {
        $credentials = $destination->googleOAuthCredentials();
        $response = Http::asForm()->timeout(30)->withoutRedirecting()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $credentials['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
            'redirect_uri' => route('admin.backups.google.callback', $destination),
            'grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $verifier,
        ]);
        $this->check($response);
        $tokens = $response->json();
        if (empty($tokens['access_token']) || empty($tokens['refresh_token']) || ! in_array(self::SCOPE, explode(' ', $tokens['scope'] ?? ''), true)) {
            throw new RuntimeException('A Google não autorizou o acesso necessário. Volte a ligar a conta e autorize os backups.');
        }

        return $this->normaliseTokens($tokens);
    }

    public function put(BackupDestination $destination, string $file, string $filename): string
    {
        $size = filesize($file);
        if (! $size) {
            throw new RuntimeException('O ficheiro do backup está vazio.');
        }
        $metadata = ['name' => basename($filename), 'appProperties' => ['cms_backup' => '1']];
        if ($destination->google_folder_id) {
            $metadata['parents'] = [$destination->google_folder_id];
        }
        $start = $this->request($destination)->withHeaders(['X-Upload-Content-Type' => 'application/zip', 'X-Upload-Content-Length' => (string) $size])
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id', $metadata);
        $this->check($start);
        $url = $start->header('Location');
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'www.googleapis.com' || isset($parts['user']) || isset($parts['port']) || ! str_starts_with($parts['path'] ?? '', '/upload/drive/')) {
            throw new RuntimeException('A Google devolveu um endereço de envio inválido.');
        }
        $stream = fopen($file, 'rb');
        if (! $stream) {
            throw new RuntimeException('Não foi possível abrir o backup.');
        }
        try {
            $offset = 0;
            while (! feof($stream)) {
                $chunk = fread($stream, 8 * 1024 * 1024);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $end = $offset + strlen($chunk) - 1;
                $response = $this->request($destination)->withHeaders(['Content-Range' => "bytes $offset-$end/$size"])
                    ->withBody($chunk, 'application/zip')->put($url);
                if ($end + 1 < $size) {
                    if ($response->status() !== 308) {
                        throw new RuntimeException('O envio do backup para a Google foi interrompido.');
                    }
                    if ($response->header('Range') !== 'bytes=0-'.$end) {
                        throw new RuntimeException('A Google não confirmou todos os dados enviados.');
                    }
                } else {
                    $this->check($response);
                    $id = $response->json('id');
                    if (! is_string($id) || ! preg_match('/^[a-zA-Z0-9_-]+$/D', $id)) {
                        throw new RuntimeException('A Google não confirmou o backup.');
                    }

                    return 'drive/'.$id;
                }
                $offset = $end + 1;
            }
        } finally {
            fclose($stream);
        }
        throw new RuntimeException('O envio do backup não terminou.');
    }

    public function createFolder(BackupDestination $destination): string
    {
        $response = $this->request($destination)->post(self::API.'?fields=id', [
            'name' => $destination->remote_path, 'mimeType' => 'application/vnd.google-apps.folder',
            'appProperties' => ['cms_backup' => '1'],
        ]);
        $this->check($response);
        $id = $response->json('id');
        if (! is_string($id) || ! preg_match('/^[a-zA-Z0-9_-]+$/D', $id)) {
            throw new RuntimeException('Não foi possível criar a pasta de backups.');
        }

        return $id;
    }

    public function resolveFolder(BackupDestination $destination): string
    {
        $id = $destination->google_folder_id;
        if (is_string($id) && preg_match('/^[a-zA-Z0-9_-]+$/D', $id)) {
            $response = $this->request($destination)->get(self::API.'/'.$id, ['fields' => 'id,mimeType,trashed,appProperties']);
            if (! in_array($response->status(), [403, 404], true)) {
                $this->check($response);
                if ($response->json('id') === $id && $response->json('mimeType') === 'application/vnd.google-apps.folder' && ! $response->json('trashed') && $response->json('appProperties.cms_backup') === '1') {
                    return $id;
                }
            }
        }

        return $this->createFolder($destination);
    }

    public function delete(BackupDestination $destination, string $path): void
    {
        $url = $this->fileUrl($path);
        $metadata = $this->request($destination)->get($url, ['fields' => 'appProperties']);
        if ($metadata->status() === 404) {
            return;
        }
        $this->check($metadata);
        if ($metadata->json('appProperties.cms_backup') !== '1') {
            throw new RuntimeException('Este ficheiro não foi criado pelo sistema de backups.');
        }
        $response = $this->request($destination)->delete($url);
        if ($response->status() !== 404) {
            $this->check($response);
        }
    }

    public function download(BackupDestination $destination, string $path, string $target): void
    {
        $response = $this->request($destination)->withOptions(['sink' => $target])->get($this->fileUrl($path), ['alt' => 'media']);
        $this->check($response);
    }

    private function request(BackupDestination $destination): PendingRequest
    {
        $tokens = $destination->google_tokens ?: [];
        if (empty($tokens['refresh_token'])) {
            throw new RuntimeException('Ligue a conta Google antes de usar este destino.');
        }
        if (empty($tokens['access_token']) || ($tokens['expires_at'] ?? 0) < time() + 60) {
            $response = Http::asForm()->timeout(30)->withoutRedirecting()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $destination->googleOAuthCredentials()['client_id'] ?? null, 'client_secret' => $destination->googleOAuthCredentials()['client_secret'] ?? null,
                'grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token'],
            ]);
            $this->check($response);
            $tokens = $this->normaliseTokens(array_replace($tokens, $response->json()));
            if (empty($tokens['access_token'])) {
                throw new RuntimeException('É necessário voltar a ligar a conta Google.');
            }
            $destination->google_tokens = $tokens;
            if ($destination->exists && ! $destination->isDirty(['google_client_id', 'google_client_secret'])) {
                $destination->save();
            }
        }

        return Http::withToken($tokens['access_token'])->timeout(max(30, (int) $destination->timeout))->withoutRedirecting();
    }

    private function normaliseTokens(array $tokens): array
    {
        return array_intersect_key($tokens, array_flip(['access_token', 'refresh_token', 'scope'])) + ['expires_at' => time() + (int) ($tokens['expires_in'] ?? 3600)];
    }

    private function fileUrl(string $path): string
    {
        if (! preg_match('~^drive/([a-zA-Z0-9_-]+)$~D', $path, $match)) {
            throw new RuntimeException('Referência de backup Google inválida.');
        }

        return self::API.'/'.$match[1];
    }

    private function check(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException('A Google não concluiu o pedido. Verifique a ligação, as autorizações e o espaço disponível.');
        }
    }
}
