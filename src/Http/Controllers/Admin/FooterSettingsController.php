<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateFooterSettingsRequest;
use Pcteckserv\CmsCore\Support\SiteOptions;

class FooterSettingsController extends Controller
{
    public function edit(SiteOptions $siteOptions): View
    {
        Gate::authorize('footer.view-settings');

        return view('cms-core::admin.footer.edit', [
            'options' => $siteOptions->all(),
        ]);
    }

    public function update(UpdateFooterSettingsRequest $request, SiteOptions $siteOptions): RedirectResponse
    {
        $siteOptions->setMany($request->validated());

        return back()->with('cms_footer_settings_success', 'Footer guardado com sucesso.');
    }
}
