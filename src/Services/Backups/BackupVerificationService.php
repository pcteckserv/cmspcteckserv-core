<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Storage;
use Pcteckserv\CmsCore\Models\BackupRun;
use RuntimeException;
use ZipArchive;

class BackupVerificationService
{
    public function verify(BackupRun $run): bool
    {
        if (! $run->local_path || ! Storage::disk(config('cms-backups.local_disk', 'local'))->exists($run->local_path)) {
            throw new RuntimeException('O ficheiro local do backup não existe.');
        }

        $path = Storage::disk(config('cms-backups.local_disk', 'local'))->path($run->local_path);

        if ($run->checksum_sha256 && ! hash_equals($run->checksum_sha256, hash_file('sha256', $path))) {
            throw new RuntimeException('O checksum do backup não corresponde ao valor guardado.');
        }

        $zip = new ZipArchive();
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
    }
}
