<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Models\Media;
use Pcteckserv\CmsCore\Seo\Events\SeoSettingsUpdated;
use Pcteckserv\CmsCore\Seo\Http\Requests\UpdateSeoSettingsRequest;
use Pcteckserv\CmsCore\Seo\Services\RobotsTxtGenerator;
use Pcteckserv\CmsCore\Seo\Services\SitemapGenerator;
use Pcteckserv\CmsCore\Services\Media\MediaService;
use Pcteckserv\CmsCore\Support\SiteOptions;

class SeoSettingsController extends Controller
{
    public function edit(SiteOptions $siteOptions): View
    {
        Gate::authorize('seo.settings.manage');

        return view('cms-core::admin.seo.settings', ['options' => $siteOptions->all()]);
    }

    public function update(UpdateSeoSettingsRequest $request, SiteOptions $siteOptions, ActivityLoggerContract $logger): RedirectResponse
    {
        $validated = $request->validated();

        foreach (['seo_default_og_image', 'seo_organization_logo'] as $field) {
            $mediaIdField = $field.'_media_id';

            if (! empty($validated[$mediaIdField])) {
                Gate::authorize('media.view');
                $media = Media::query()->where('media_type', 'image')->findOrFail($validated[$mediaIdField]);
                $validated[$field] = app(MediaService::class)->url($media, 'optimized');
            }

            unset($validated[$mediaIdField]);
        }

        $siteOptions->setMany($validated);
        app(SitemapGenerator::class)->clearCache();
        app(RobotsTxtGenerator::class)->clearCache();

        $logger->log('seo.settings.updated', 'SEO', 'Configurações SEO globais alteradas.');
        event(new SeoSettingsUpdated($validated));

        return back()->with('seo_success', 'Configurações SEO guardadas com sucesso.');
    }
}
