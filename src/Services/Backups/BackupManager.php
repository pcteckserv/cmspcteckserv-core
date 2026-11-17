<?php

namespace Pcteckserv\CmsCore\Services\Backups;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Events\BackupCompleted;
use Pcteckserv\CmsCore\Events\BackupFailed;
use Pcteckserv\CmsCore\Events\BackupStarted;
use Pcteckserv\CmsCore\Events\BackupUploaded;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Services\Backups\BackupException as RuntimeException;
use Throwable;
use ZipArchive;

class BackupManager
{
    public function __construct(
        private readonly BackupStorageService $storage,
        private readonly BackupRetentionService $retention,
        private readonly BackupNotificationService $notifications,
        private readonly BackupSchedulerService $scheduler,
        private readonly BackupDatabaseExporter $database,
    ) {}

    public function create(?BackupPlan $plan, string $type, string $origin = 'manual', ?int $userId = null, ?string $storageMode = null): BackupRun
    {
        if (! in_array($type, ['full', 'database', 'files', 'media', 'images'], true) || ($storageMode && ! in_array($storageMode, ['local', 'remote', 'local_and_remote'], true))) {
            throw new RuntimeException('Opções de backup inválidas.');
        }

        return Cache::lock('cms-backups:create', 3600)->block(1, function () use ($plan, $type, $origin, $userId, $storageMode): BackupRun {
            $run = BackupRun::query()->create([
                'plan_id' => $plan?->id,
                'destination_id' => $plan?->destination_id,
                'user_id' => $userId,
                'type' => $type,
                'origin' => $origin,
                'status' => 'running',
                'storage_mode' => $storageMode ?: $plan?->storage_mode ?: 'local_and_remote',
                'started_at' => now(),
                'destination_snapshot' => $plan?->destination?->snapshot(),
            ]);
            Event::dispatch(new BackupStarted($run));

            try {
                $filename = $this->filename($plan, $type);
                $temporaryFile = $this->temporaryFile($filename);
                $manifest = $this->manifest($plan, $type);
                $sizeBefore = $this->buildArchive($temporaryFile, $manifest, $plan, $type);
                $checksum = hash_file('sha256', $temporaryFile);
                $localPath = null;
                $remotePath = null;
                $status = 'success';

                if (in_array($run->storage_mode, ['remote', 'local_and_remote'], true)) {
                    try {
                        if (! $plan?->destination) {
                            throw new RuntimeException('Configure um destino antes de criar o backup remoto.');
                        }
                        $remotePath = $this->storage->put($plan->destination, $temporaryFile, $filename);
                        Event::dispatch(new BackupUploaded($run));
                    } catch (Throwable $exception) {
                        $status = $run->storage_mode === 'remote' ? 'failed' : 'partial';
                        $run->failure_reason = 'O backup foi criado localmente, mas o envio remoto falhou.'.($exception instanceof RuntimeException ? ' '.$exception->getMessage() : '');
                        report($exception);
                    }
                }

                // Se o remoto falhar, conservar uma cópia local recuperável, mesmo no modo remoto.
                if ($run->storage_mode !== 'remote' || $status === 'failed') {
                    $localPath = $this->storeLocal($temporaryFile, $filename);
                }

                $run->fill([
                    'status' => $status,
                    'filename' => $filename,
                    'local_path' => $localPath,
                    'remote_path' => $remotePath,
                    'size_before_compression' => $sizeBefore,
                    'size_bytes' => filesize($temporaryFile) ?: null,
                    'checksum_sha256' => $checksum,
                    'manifest' => $manifest,
                    'finished_at' => now(),
                    'duration_seconds' => max(0, (int) $run->started_at->diffInSeconds(now())),
                ])->save();

                @unlink($temporaryFile);
                if ($status === 'success') {
                    try {
                        $this->retention->apply($plan);
                    } catch (Throwable $exception) {
                        report($exception);
                        BackupAuditLog::create(['action' => 'backups.retention', 'result' => 'failed', 'context' => ['plan_id' => $plan?->id]]);
                    }
                }

                if ($plan) {
                    $plan->forceFill([
                        'last_run_at' => now(),
                        'next_run_at' => $this->scheduler->nextRunAt($plan),
                    ])->save();
                }

                $this->notifications->notifyRunFinished($run);
                Event::dispatch(new BackupCompleted($run));

                return $run;
            } catch (Throwable $exception) {
                if (isset($temporaryFile)) {
                    @unlink($temporaryFile);
                }
                $run->forceFill([
                    'status' => 'failed',
                    'failure_reason' => $exception instanceof RuntimeException ? $exception->getMessage() : 'Não foi possível criar o backup.',
                    'finished_at' => now(),
                    'duration_seconds' => max(0, (int) $run->started_at->diffInSeconds(now())),
                ])->save();

                report($exception);
                $this->notifications->notifyRunFinished($run);
                Event::dispatch(new BackupFailed($run));

                return $run;
            }
        }) ?? throw new RuntimeException('Já existe um backup em execução.');
    }

    private function buildArchive(string $path, array $manifest, ?BackupPlan $plan, string $type): int
    {
        $databaseFile = null;
        $databaseDirectory = dirname($path).'/database-'.Str::uuid();
        File::ensureDirectoryExists($databaseDirectory, 0700);
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo ZIP.');
        }

        $closed = false;
        try {
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            if (in_array($type, ['database', 'full'], true)) {
                $databaseFile = $this->database->export($databaseDirectory);
                $zip->addFile($databaseFile, 'database/'.basename($databaseFile));
            }

            if (in_array($type, ['files', 'media', 'images', 'full'], true)) {
                $paths = in_array($type, ['media', 'images'], true) ? config('cms-backups.media_paths', []) : ($type === 'full' ? config('cms-backups.full_included_paths', ['.']) : ($plan?->included_paths ?: config('cms-backups.default_included_paths', [])));
                foreach ($paths as $relativePath) {
                    $this->addPath($zip, $relativePath, $plan, $type === 'images');
                }
            }
            $closed = true;
            if (! $zip->close()) {
                throw new RuntimeException('Não foi possível concluir o arquivo ZIP.');
            }
        } finally {
            if (! $closed) {
                $zip->close();
            }
            // Diretoria criada exclusivamente por esta execução dentro da área temporária.
            if ($databaseFile) {
                @unlink($databaseFile);
            }
            @unlink($databaseDirectory.'/database-client.cnf');
            @unlink($databaseDirectory.'/database.sql');
            @unlink($databaseDirectory.'/database.sqlite');
            @rmdir($databaseDirectory);
        }

        return filesize($path) ?: 0;
    }

    private function addPath(ZipArchive $zip, string $relativePath, ?BackupPlan $plan, bool $imagesOnly = false): void
    {
        $base = rtrim(str_replace('\\', '/', realpath(base_path())), '/');
        $absolutePath = realpath(base_path($relativePath));
        if ($absolutePath === false) {
            return;
        }
        $normal = str_replace('\\', '/', $absolutePath);
        if ($normal !== $base && ! str_starts_with($normal, $base.'/')) {
            throw new RuntimeException('Um diretório de backup está fora do site.');
        }
        $exclusions = array_merge(config('cms-backups.default_excluded_paths', []), $plan?->excluded_paths ?: []);
        $exclusions[] = str_replace('\\', '/', config('cms-backups.temporary_path'));
        $exclusions[] = str_replace('\\', '/', Storage::disk(config('cms-backups.local_disk', 'local'))->path(config('cms-backups.local_path', 'cms-backups')));
        $allowed = static function (\SplFileInfo $file) use ($base, $exclusions, $imagesOnly): bool {
            if ($file->isLink()) {
                return false;
            }
            if ($imagesOnly && $file->isFile() && ! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'svg', 'bmp', 'ico', 'tif', 'tiff', 'heic', 'heif'], true)) {
                return false;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            if (preg_match('~(^|/)\.env(?:\.[^/]*)?$~', $path)) {
                return false;
            }
            foreach ($exclusions as $excluded) {
                $excluded = rtrim(str_replace('\\', '/', $excluded), '/');
                if ($excluded === '') {
                    continue;
                }
                if (! str_starts_with($excluded, '/') && ! preg_match('/^[A-Za-z]:/', $excluded)) {
                    $excluded = $base.'/'.ltrim($excluded, '/');
                }
                if ($path === $excluded || str_starts_with($path, $excluded.'/')) {
                    return false;
                }
            }

            return true;
        };
        if (is_file($absolutePath)) {
            if ($allowed(new \SplFileInfo($absolutePath))) {
                $zip->addFile($absolutePath, 'files/'.substr($normal, strlen($base) + 1));
            }

            return;
        }
        $directory = new \RecursiveDirectoryIterator($absolutePath, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator($directory, $allowed));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), 'files/'.substr(str_replace('\\', '/', $file->getPathname()), strlen($base) + 1));
            }
        }
    }

    private function storeLocal(string $temporaryFile, string $filename): string
    {
        $path = trim(config('cms-backups.local_path', 'cms-backups'), '/').'/'.$filename;
        $stream = fopen($temporaryFile, 'rb');
        if (! $stream) {
            throw new RuntimeException('Não foi possível abrir o backup local.');
        }
        try {
            if (! Storage::disk(config('cms-backups.local_disk', 'local'))->put($path, $stream)) {
                throw new RuntimeException('Não foi possível guardar o backup local.');
            }
        } finally {
            fclose($stream);
        }

        return $path;
    }

    private function temporaryFile(string $filename): string
    {
        $directory = config('cms-backups.temporary_path');
        File::ensureDirectoryExists($directory, 0750);

        return $directory.DIRECTORY_SEPARATOR.Str::uuid().'-'.$filename;
    }

    private function filename(?BackupPlan $plan, string $type): string
    {
        $site = Str::slug(config('app.name', 'cms-pcteck'));

        return $site.'-'.$type.'-'.now()->format('Y-m-d-His').'-'.Str::uuid().'.zip';
    }

    private function manifest(?BackupPlan $plan, string $type): array
    {
        return [
            'version' => 1,
            'cms_core_version' => null,
            'laravel_version' => app()->version(),
            'created_at' => now()->toISOString(),
            'type' => $type,
            'plan' => $plan?->name,
            'database' => in_array($type, ['database', 'full'], true),
            'files' => in_array($type, ['files', 'media', 'images', 'full'], true),
        ];
    }
}
