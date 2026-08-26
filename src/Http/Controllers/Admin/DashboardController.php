<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class DashboardController extends Controller
{
    public function __invoke(MaintenanceModeManager $maintenance): View
    {
        return view('cms-core::admin.dashboard', [
            'maintenance' => $maintenance->settings(),
            'maintenanceIsActive' => $maintenance->isActive(),
        ]);
    }
}
