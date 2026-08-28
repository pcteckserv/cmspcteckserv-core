<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Consent;

use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Consent\Scanner\ConsentRouteDiscoverer;
use Pcteckserv\CmsCore\Jobs\RunConsentScanJob;
use Pcteckserv\CmsCore\Models\ConsentScan;

class ConsentScansController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('consent.scan'), 403);

        return view('cms-core::admin.consent.scans', ['scans' => ConsentScan::query()->latest()->paginate(20)]);
    }

    public function store(ConsentRouteDiscoverer $discoverer)
    {
        abort_unless(auth()->user()?->can('consent.scan'), 403);
        $urls = $discoverer->discover(array_filter(array_map('trim', explode("\n", (string) request('urls')))));
        $scan = ConsentScan::query()->create(['status' => 'pending', 'urls' => $urls]);
        RunConsentScanJob::dispatch($scan->id, $urls);

        return redirect()->route('admin.consent.scans.index')->with('status', 'Análise iniciada.');
    }
}
