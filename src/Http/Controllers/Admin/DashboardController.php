<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Contracts\CmsAccessUser;
use Pcteckserv\CmsCore\Services\Auth\TwoFactorManager;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class DashboardController extends Controller
{
    public function __invoke(MaintenanceModeManager $maintenance, TwoFactorManager $twoFactor): View
    {
        $user = auth()->user();
        $administrator = $user instanceof CmsAccessUser && ($user->isCmsSuperAdmin() || $user->hasCmsRole('core.admin'));

        return view('cms-core::admin.dashboard', [
            'maintenance' => $maintenance->settings(),
            'maintenanceIsActive' => $maintenance->isActive(),
            'needsTwoFactorSetup' => $administrator && $twoFactor->enabled($user) === null,
        ]);
    }
}
