<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Pcteckserv\CmsCore\Support\SiteOptions;

class SiteOptionsController extends Controller
{
    public function edit(SiteOptions $siteOptions): View
    {
        return view('cms-core::admin.site-options.edit', [
            'options' => $siteOptions->all(),
            'locales' => $this->locales(),
        ]);
    }

    public function update(Request $request, SiteOptions $siteOptions): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['required', 'string', 'max:120'],
            'site_description' => ['nullable', 'string', 'max:255'],
            'site_icon_url' => ['nullable', 'string', 'max:2048'],
            'site_icon_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'max:2048'],
            'wordpress_url' => ['required', 'url', 'max:2048'],
            'site_url' => ['required', 'url', 'max:2048'],
            'admin_email' => ['required', 'email', 'max:255'],
            'locale' => ['required', Rule::in(array_keys($this->locales()))],
        ]);

        if ($request->hasFile('site_icon_file')) {
            $validated['site_icon_url'] = Storage::disk('public')->url(
                $request->file('site_icon_file')->store('cms/site-icons', 'public')
            );
        }

        unset($validated['site_icon_file']);

        $siteOptions->setMany($validated);

        return back()->with('cms_site_options_success', 'Opcoes gerais guardadas com sucesso.');
    }

    private function locales(): array
    {
        return [
            'pt_PT' => 'Portugues',
            'en_US' => 'Ingles',
            'es_ES' => 'Espanhol',
            'fr_FR' => 'Frances',
        ];
    }
}
