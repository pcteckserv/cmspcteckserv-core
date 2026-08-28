<?php

namespace Pcteckserv\CmsCore\Seo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Seo\Models\SeoNotFound;

class SeoNotFoundController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('seo.404.view');
        $query = SeoNotFound::query()->latest('last_seen_at');

        if ($search = $request->string('search')->toString()) {
            $query->where('url', 'like', '%'.$search.'%');
        }

        return view('cms-core::admin.seo.not-found', [
            'items' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function update(Request $request, SeoNotFound $notFound): RedirectResponse
    {
        Gate::authorize('seo.404.manage');
        $validated = $request->validate([
            'is_ignored' => ['nullable', 'boolean'],
            'is_resolved' => ['nullable', 'boolean'],
        ]);

        $notFound->update([
            'is_ignored' => (bool) ($validated['is_ignored'] ?? false),
            'is_resolved' => (bool) ($validated['is_resolved'] ?? false),
        ]);

        return back()->with('seo_success', 'Erro 404 atualizado com sucesso.');
    }
}
