<?php

namespace Pcteckserv\CmsCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Consent\Events\ConsentScanCompleted;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentScanner;
use Pcteckserv\CmsCore\Models\ConsentScan;

class RunConsentScanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $scanId, public array $urls)
    {
    }

    public function handle(ConsentScanner $scanner, ActivityLoggerContract $activityLogger): void
    {
        $scan = $scanner->scan(ConsentScan::query()->findOrFail($this->scanId), $this->urls);
        $activityLogger->log('consent.scan.completed', 'Consentimentos', 'Análise de consentimentos concluída.', $scan, ['status' => $scan->status]);
        event(new ConsentScanCompleted($scan));
    }
}
