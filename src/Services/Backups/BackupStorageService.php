<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;

class BackupStorageService
{
    public function __construct(private readonly GoogleDriveBackupService $google, private readonly FtpBackupService $ftp) {}

    public function put(BackupDestination $destination, string $localFile, string $filename): string
    {
        if ($destination->protocol === 'google') {
            return $this->google->put($destination, $localFile, $filename);
        }
        $path = rtrim($destination->remote_path, '/').'/'.basename($filename);
        $this->assertSafeRemotePath($destination, $path);
        if (in_array($destination->protocol, ['ftp', 'ftps'], true)) {
            $this->ftp->put($destination, $localFile, $path);
        } else {
            $stream = fopen($localFile, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Não foi possível abrir o backup.');
            }
            try {
                if (! Storage::disk($destination->disk)->put($path, $stream)) {
                    throw new RuntimeException('Não foi possível enviar o backup.');
                }
            } finally {
                fclose($stream);
            }
            if (! Storage::disk($destination->disk)->exists($path)) {
                throw new RuntimeException('O destino não confirmou o backup.');
            }
        }

        return $path;
    }

    public function delete(BackupDestination $destination, string $path): void
    {
        if ($destination->protocol === 'google') {
            $this->google->delete($destination, $path);

            return;
        }
        $this->assertSafeRemotePath($destination, $path);
        if (in_array($destination->protocol, ['ftp', 'ftps'], true)) {
            $this->ftp->delete($destination, $path);
        } elseif (! Storage::disk($destination->disk)->delete($path)) {
            throw new RuntimeException('Não foi possível eliminar o backup remoto.');
        }
    }

    public function download(BackupDestination $destination, string $path, string $target): void
    {
        if ($destination->protocol === 'google') {
            $this->google->download($destination, $path, $target);

            return;
        }
        $this->assertSafeRemotePath($destination, $path);
        if (in_array($destination->protocol, ['ftp', 'ftps'], true)) {
            $this->ftp->download($destination, $path, $target);

            return;
        }
        $input = Storage::disk($destination->disk)->readStream($path);
        if (! is_resource($input)) {
            throw new RuntimeException('Não foi possível ler o backup.');
        }
        $output = fopen($target, 'wb');
        try {
            if (! $output || stream_copy_to_stream($input, $output) === false) {
                throw new RuntimeException('Não foi possível ler o backup.');
            }
        } finally {
            fclose($input);
            if (is_resource($output)) {
                fclose($output);
            }
        }
    }

    public function test(BackupDestination $destination): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cms-backup-test-');
        if ($file === false) {
            throw new RuntimeException('Não foi possível criar o teste.');
        }
        file_put_contents($file, 'teste');
        $path = null;
        try {
            $path = $this->put($destination, $file, '.cms-backup-test-'.Str::uuid().'.txt');
        } finally {
            @unlink($file);
            if ($path !== null) {
                $this->delete($destination, $path);
            }
        }
    }

    private function assertSafeRemotePath(BackupDestination $destination, string $path): void
    {
        $root = rtrim($destination->remote_path, '/').'/';
        if (preg_match('~(^|/)\.\.(/|$)|[\x00-\x1f\\\\]~', $path) || ! str_starts_with($path, $root) || $path === $root) {
            throw new RuntimeException('Caminho remoto inválido.');
        }
    }
}
