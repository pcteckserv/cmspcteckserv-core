<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Models\BackupDestination;
use RuntimeException;
use Throwable;

class BackupStorageService
{
    public function put(BackupDestination $destination, string $localFile, string $filename): string
    {
        $remotePath = $this->join($destination->remote_path, $filename);
        $stream = fopen($localFile, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Não foi possível abrir o ficheiro local do backup.');
        }

        try {
            Storage::disk($destination->disk)->put($remotePath, $stream);
        } finally {
            fclose($stream);
        }

        if (! Storage::disk($destination->disk)->exists($remotePath)) {
            throw new RuntimeException('O ficheiro remoto não foi encontrado após o envio.');
        }

        return $remotePath;
    }

    public function delete(BackupDestination $destination, string $path): void
    {
        $this->assertSafeRemotePath($destination, $path);
        Storage::disk($destination->disk)->delete($path);
    }

    public function test(BackupDestination $destination): void
    {
        $filename = '.cms-backup-test-'.Str::uuid().'.txt';
        $path = $this->join($destination->remote_path, $filename);

        try {
            Storage::disk($destination->disk)->put($path, 'teste');

            if (! Storage::disk($destination->disk)->exists($path)) {
                throw new RuntimeException('Não foi possível confirmar o ficheiro de teste.');
            }
        } finally {
            try {
                Storage::disk($destination->disk)->delete($path);
            } catch (Throwable) {
                //
            }
        }
    }

    private function join(string $directory, string $filename): string
    {
        return trim($directory, '/').'/'.basename($filename);
    }

    private function assertSafeRemotePath(BackupDestination $destination, string $path): void
    {
        $root = trim($destination->remote_path, '/').'/';
        $path = trim($path, '/');

        if (str_contains($path, '../') || ! str_starts_with($path, $root)) {
            throw new RuntimeException('Caminho remoto inválido.');
        }
    }
}
