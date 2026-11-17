<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;
use ZipArchive;

class BackupVerificationService
{
    public function __construct(private readonly BackupStorageService $storage) {}

    public function verify(BackupRun $run): bool
    {
        $temporary = null;
        $disk = Storage::disk(config('cms-backups.local_disk', 'local'));
        if ($run->local_path && $disk->exists($run->local_path)) {
            $path = $disk->path($run->local_path);
        } elseif ($run->remote_path && $run->storageDestination()) {
            $temporary = tempnam(sys_get_temp_dir(), 'cms-backup-verify-');
            if ($temporary === false) {
                throw new RuntimeException('Não foi possível preparar a verificação.');
            }
            $path = $temporary;
        } else {
            throw new RuntimeException('O ficheiro do backup não existe.');
        }
        try {
            if ($temporary !== null) {
                $this->storage->download($run->storageDestination(), $run->remote_path, $path);
            }

            if ($run->checksum_sha256 && ! hash_equals($run->checksum_sha256, hash_file('sha256', $path))) {
                throw new RuntimeException('O checksum do backup não corresponde ao valor guardado.');
            }

            $zip = new ZipArchive;
            if ($zip->open($path) !== true) {
                throw new RuntimeException('Não foi possível abrir o ficheiro ZIP.');
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false || str_contains($name, '../') || str_starts_with($name, '/')) {
                    $zip->close();
                    throw new RuntimeException('O backup contém caminhos inválidos.');
                }
            }

            $zip->close();

            return true;
        } finally {
            if ($temporary !== null) {
                @unlink($temporary);
            }
        }
    }
}
