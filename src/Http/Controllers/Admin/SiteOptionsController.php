<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateSiteOptionsRequest;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Services\Media\MediaService;
use Pcteckserv\CmsCore\Support\SiteOptions;

class SiteOptionsController extends Controller
{
    public function edit(SiteOptions $siteOptions): View
    {
        Gate::authorize('core.site-options.view');

        return view('cms-core::admin.site-options.edit', [
            'options' => $siteOptions->all(),
            'locales' => $this->locales(),
            'defaultSiteIconUrl' => $this->defaultSiteIconUrl(),
        ]);
    }

    public function update(UpdateSiteOptionsRequest $request, SiteOptions $siteOptions, MediaService $mediaService): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->boolean('remove_site_icon')) {
            $this->deleteStoredSiteIcon($validated['site_icon_url'] ?? null);
            $validated['site_icon_url'] = $this->defaultSiteIconUrl();
        } elseif (! empty($validated['site_icon_media_id'])) {
            $media = Media::query()
                ->where('media_type', 'image')
                ->findOrFail($validated['site_icon_media_id']);

            $validated['site_icon_url'] = $mediaService->url($media, 'optimized');
        }

        unset($validated['site_icon_media_id'], $validated['remove_site_icon']);

        $siteOptions->setMany($validated);

        return back()->with('cms_site_options_success', 'Opções gerais guardadas com sucesso.');
    }

    private function locales(): array
    {
        return [
            'pt_PT' => 'Português',
            'en_US' => 'Inglês',
            'es_ES' => 'Espanhol',
            'fr_FR' => 'Francês',
        ];
    }

    private function deleteStoredSiteIcon(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/storage/cms/site-icons/')) {
            return;
        }

        Storage::disk('public')->delete(Str::after($path, '/storage/'));
    }

    private function defaultSiteIconUrl(): string
    {
        $default = config('cms-core.site_options.site_icon_url');

        return is_string($default) && $default !== ''
            ? $default
            : '/vendor/cms-core/images/favicon.png';
    }
}
