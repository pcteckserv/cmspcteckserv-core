<?php

namespace Pcteckserv\CmsCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;
use Throwable;

class UpdatePackageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public readonly string $package,
        public readonly ?int $userId = null,
    ) {
    }

    public function handle(
        PackageUpdater $updater,
        PackageVersionRegistry $registry,
        UpdateStatusRepository $statuses,
    ): void {
        $statuses->markRunning($this->package, $this->userId);

        $result = $updater->update($this->package);
        $registry->checkRemoteUpdates();

        $statuses->markFinished($this->package, $result, $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        app(UpdateStatusRepository::class)->markFailed(
            $this->package,
            'A atualização falhou: '.$exception->getMessage(),
            $this->userId,
        );
    }
}
