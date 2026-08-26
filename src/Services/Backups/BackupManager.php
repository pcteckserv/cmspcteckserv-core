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
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupManager
{
    public function __construct(
        private readonly BackupStorageService $storage,
        private readonly BackupRetentionService $retention,
        private readonly BackupNotificationService $notifications,
        private readonly BackupSchedulerService $scheduler,
    ) {
    }

    public function create(?BackupPlan $plan, string $type, string $origin = 'manual', ?int $userId = null, ?string $storageMode = null): BackupRun
    {
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
            ]);
            Event::dispatch(new BackupStarted($run));

            try {
                $filename = $this->filename($plan, $type);
                $temporaryFile = $this->temporaryFile($filename);
                $manifest = $this->manifest($plan, $type);
                $sizeBefore = $this->buildArchive($temporaryFile, $manifest, $plan, $type);
                $checksum = hash_file('sha256', $temporaryFile);
                $localPath = $this->storeLocal($temporaryFile, $filename);
                $remotePath = null;
                $status = 'success';

                if (in_array($run->storage_mode, ['remote', 'local_and_remote'], true) && $plan?->destination) {
                    try {
                        $remotePath = $this->storage->put($plan->destination, $temporaryFile, $filename);
                        Event::dispatch(new BackupUploaded($run));
                    } catch (Throwable $exception) {
                        $status = $run->storage_mode === 'remote' ? 'failed' : 'partial';
                        $run->failure_reason = 'O backup foi criado localmente, mas o envio remoto falhou.';
                        report($exception);
                    }
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
                    'duration_seconds' => now()->diffInSeconds($run->started_at),
                ])->save();

                @unlink($temporaryFile);
                $this->retention->apply($plan);

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
                $run->forceFill([
                    'status' => 'failed',
                    'failure_reason' => 'Não foi possível criar o backup.',
                    'finished_at' => now(),
                    'duration_seconds' => now()->diffInSeconds($run->started_at),
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
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo ZIP.');
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (in_array($type, ['database', 'full'], true)) {
            $zip->addFromString('database/README.txt', 'Dump por ferramenta nativa deve ser configurado no servidor.');
        }

        if (in_array($type, ['files', 'media', 'full'], true)) {
            foreach ($plan?->included_paths ?: config('cms-backups.default_included_paths', []) as $relativePath) {
                $this->addPath($zip, base_path($relativePath), 'files/'.trim($relativePath, '/'));
            }
        }

        $zip->close();

        return filesize($path) ?: 0;
    }

    private function addPath(ZipArchive $zip, string $absolutePath, string $archivePath): void
    {
        if (! File::exists($absolutePath)) {
            return;
        }

        if (File::isFile($absolutePath)) {
            $zip->addFile($absolutePath, $archivePath);

            return;
        }

        foreach (File::allFiles($absolutePath) as $file) {
            $realPath = $file->getRealPath();
            if ($realPath && ! str_contains($realPath, DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR)) {
                $zip->addFile($realPath, $archivePath.'/'.str_replace('\\', '/', $file->getRelativePathname()));
            }
        }
    }

    private function storeLocal(string $temporaryFile, string $filename): string
    {
        $path = trim(config('cms-backups.local_path', 'cms-backups'), '/').'/'.$filename;
        Storage::disk(config('cms-backups.local_disk', 'local'))->put($path, fopen($temporaryFile, 'rb'));

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

        return $site.'-'.$type.'-'.now()->format('Y-m-d-His').'.zip';
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
            'files' => in_array($type, ['files', 'media', 'full'], true),
        ];
    }
}
