<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Seo\Models\SeoAudit;
use Pcteckserv\CmsCore\Seo\Models\SeoNotFound;
use Pcteckserv\CmsCore\Seo\Models\SeoRedirect;

class SeoDashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('seo.view');

        return view('cms-core::admin.seo.dashboard', [
            'averageScore' => (int) SeoAudit::query()->avg('score'),
            'auditedPages' => SeoAudit::query()->count(),
            'criticalIssues' => SeoAudit::query()->whereJsonContains('results', [['status' => 'critical']])->count(),
            'warnings' => SeoAudit::query()->whereJsonContains('results', [['status' => 'warning']])->count(),
            'notFoundCount' => SeoNotFound::query()->where('is_ignored', false)->where('is_resolved', false)->count(),
            'redirectCount' => SeoRedirect::query()->count(),
        ]);
    }
}
