<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Seo\Events\RedirectCreated;
use Pcteckserv\CmsCore\Seo\Events\RedirectDeleted;
use Pcteckserv\CmsCore\Seo\Events\RedirectUpdated;
use Pcteckserv\CmsCore\Seo\Http\Requests\StoreSeoRedirectRequest;
use Pcteckserv\CmsCore\Seo\Http\Requests\UpdateSeoRedirectRequest;
use Pcteckserv\CmsCore\Seo\Models\SeoRedirect;
use Pcteckserv\CmsCore\Seo\Services\RedirectResolver;

class SeoRedirectsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('seo.redirects.manage');

        return view('cms-core::admin.seo.redirects', [
            'redirects' => SeoRedirect::query()->latest()->paginate(20),
        ]);
    }

    public function store(StoreSeoRedirectRequest $request, ActivityLoggerContract $logger): RedirectResponse
    {
        $redirect = SeoRedirect::query()->create($request->validated());
        app(RedirectResolver::class)->clearCache();
        $logger->log('seo.redirect.created', 'SEO', 'Redirecionamento criado.', $redirect);
        event(new RedirectCreated($redirect));

        return back()->with('seo_success', 'Redirecionamento criado com sucesso.');
    }

    public function update(UpdateSeoRedirectRequest $request, SeoRedirect $redirect, ActivityLoggerContract $logger): RedirectResponse
    {
        $old = $redirect->only(['source', 'destination', 'status_code', 'is_active']);
        $redirect->update($request->validated());
        app(RedirectResolver::class)->clearCache();
        $logger->log('seo.redirect.updated', 'SEO', 'Redirecionamento alterado.', $redirect, [], $old, $redirect->only(array_keys($old)));
        event(new RedirectUpdated($redirect));

        return back()->with('seo_success', 'Redirecionamento guardado com sucesso.');
    }

    public function destroy(SeoRedirect $redirect, ActivityLoggerContract $logger): RedirectResponse
    {
        Gate::authorize('seo.redirects.manage');
        $source = $redirect->source;
        $redirect->delete();
        app(RedirectResolver::class)->clearCache();
        $logger->log('seo.redirect.deleted', 'SEO', 'Redirecionamento eliminado.');
        event(new RedirectDeleted($source));

        return back()->with('seo_success', 'Redirecionamento eliminado com sucesso.');
    }
}
