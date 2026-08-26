<?php

namespace Pcteckserv\CmsCore\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Services\Maintenance\MaintenanceModeManager;

class MaintenanceAccessController extends Controller
{
    public function store(Request $request, MaintenanceModeManager $maintenance): RedirectResponse
    {
        $validated = $request->validate([
            'access_code' => ['required', 'string', 'max:80'],
        ]);

        $settings = $maintenance->settings();

        if (! $settings['access_enabled'] || ! $maintenance->codeIsValid($validated['access_code'])) {
            return back()->withErrors([
                'access_code' => 'Código de acesso inválido.',
            ]);
        }

        $maintenance->grantAccess($request);

        return redirect()->to($this->intendedUrl($request));
    }

    private function intendedUrl(Request $request): string
    {
        $url = (string) $request->session()->pull(MaintenanceModeManager::INTENDED_URL_KEY, '/');

        if ($url === '' || str_starts_with($url, '//') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            return '/';
        }

        return str_starts_with($url, '/') ? $url : '/';
    }
}
