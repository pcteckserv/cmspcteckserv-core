<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateMaintenanceSettingsRequest;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceTemplateRegistry;

class MaintenanceSettingsController extends Controller
{
    public function edit(
        MaintenanceModeManager $maintenance,
        MaintenanceTemplateRegistry $templates,
    ): View {
        Gate::authorize('maintenance.view');

        return view('cms-core::admin.maintenance.edit', [
            'options' => $maintenance->options(),
            'maintenance' => $maintenance->settings(),
            'templates' => $templates->all(),
        ]);
    }

    public function update(UpdateMaintenanceSettingsRequest $request, MaintenanceModeManager $maintenance): RedirectResponse
    {
        $settings = $maintenance->settings();

        if ($request->boolean('maintenance_enabled') !== $settings['enabled']) {
            Gate::authorize('maintenance.toggle');
        }

        if (
            $request->boolean('maintenance_access_enabled')
            || $request->filled('maintenance_access_code')
            || $request->boolean('generate_maintenance_access_code')
            || $request->boolean('invalidate_maintenance_access')
        ) {
            Gate::authorize('maintenance.manage-access');
        }

        $code = $maintenance->update($request->validated(), (int) $request->user()?->getAuthIdentifier());

        return back()
            ->with('cms_maintenance_success', 'Modo de manutenção guardado com sucesso.')
            ->with('cms_maintenance_access_code', $code);
    }

    public function preview(MaintenanceModeManager $maintenance): View
    {
        Gate::authorize('maintenance.preview');

        $settings = $maintenance->settings();

        return view($settings['template_view'], [
            'maintenance' => $settings,
            'templatePreview' => true,
        ]);
    }

    public function revoke(MaintenanceModeManager $maintenance): RedirectResponse
    {
        Gate::authorize('maintenance.manage-access');

        $maintenance->revokeAccess((int) auth()->id());

        return back()->with('cms_maintenance_success', 'Acessos temporários revogados com sucesso.');
    }

    public function disable(MaintenanceModeManager $maintenance): RedirectResponse
    {
        Gate::authorize('maintenance.toggle');

        $maintenance->disable((int) auth()->id());

        return back()->with('cms_maintenance_success', 'Modo de manutenção desativado com sucesso.');
    }
}
