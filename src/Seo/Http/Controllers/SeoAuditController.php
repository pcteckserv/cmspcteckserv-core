<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Seo\Models\SeoAudit;

class SeoAuditController extends Controller
{
    public function index(): View
    {
        Gate::authorize('seo.view');

        return view('cms-core::admin.seo.audit', [
            'audits' => SeoAudit::query()->latest('scanned_at')->paginate(25),
        ]);
    }
}
