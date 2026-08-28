<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Consent;

use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentRouteDiscoverer;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentScanner;
use Pcteckserv\CmsCore\Jobs\RunConsentScanJob;
use Pcteckserv\CmsCore\Models\ConsentScan;

class ConsentScansController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('consent.scan'), 403);

        $scans = ConsentScan::query()->latest()->paginate(20);

        return view('cms-core::admin.consent.scans', [
            'scans' => $scans,
            'hasActiveScans' => $scans->getCollection()->contains(
                fn (ConsentScan $scan): bool => in_array($scan->status, ['pending', 'running'], true),
            ),
            'queueConnection' => config('queue.default', 'sync'),
        ]);
    }

    public function store(ConsentRouteDiscoverer $discoverer, ConsentScanner $scanner)
    {
        abort_unless(auth()->user()?->can('consent.scan'), 403);

        $urls = $discoverer->discover(array_filter(array_map('trim', explode("\n", (string) request('urls')))));
        $scan = ConsentScan::query()->create(['status' => 'pending', 'urls' => $urls]);

        if (request('mode') === 'now') {
            $scanner->scan($scan, $urls);

            return redirect()
                ->route('admin.consent.scans.index')
                ->with('status', 'Análise concluída. Foram analisadas '.$scan->fresh()->pages_scanned.' páginas.');
        }

        RunConsentScanJob::dispatch($scan->id, $urls);

        return redirect()
            ->route('admin.consent.scans.index')
            ->with('status', 'Análise enviada para a fila. O estado será atualizado automaticamente quando o worker processar o trabalho.');
    }
}
