<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Consent;

use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Models\ConsentScan;
use Pcteckserv\CmsCore\Models\ConsentService;
use Pcteckserv\CmsCore\Models\ConsentTechnology;

class ConsentDashboardController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()?->can('consent.view'), 403);

        return view('cms-core::admin.consent.dashboard', [
            'lastScan' => ConsentScan::query()->latest()->first(),
            'servicesCount' => ConsentService::query()->count(),
            'technologiesCount' => ConsentTechnology::query()->count(),
            'classifiedCount' => ConsentService::query()->where('review_status', 'confirmed')->count(),
            'reviewCount' => ConsentService::query()->where('review_status', 'requires_review')->count(),
        ]);
    }
}
