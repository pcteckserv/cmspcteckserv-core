<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;

class UpdatesController extends Controller
{
    public function __invoke(PackageVersionRegistry $registry): View
    {
        $packages = config('cms-core.updates.enabled', true)
            ? $registry->checkRemoteUpdates()
            : $registry->all();

        return view('cms-core::admin.updates.index', [
            'packages' => $packages,
            'updatesEnabled' => config('cms-core.updates.enabled', true),
            'channel' => config('cms-core.updates.channel', 'stable'),
        ]);
    }
}
