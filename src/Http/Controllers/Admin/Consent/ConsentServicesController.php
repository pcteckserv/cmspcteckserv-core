<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Consent;

use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;
use Pcteckserv\CmsCore\Http\Requests\Admin\Consent\UpdateConsentServiceRequest;
use Pcteckserv\CmsCore\Models\ConsentCategory;
use Pcteckserv\CmsCore\Models\ConsentService;

class ConsentServicesController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('consent.view'), 403);

        return view('cms-core::admin.consent.services', [
            'services' => ConsentService::query()->with('category')->latest()->paginate(25),
            'categories' => ConsentCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(ConsentService $service)
    {
        abort_unless(auth()->user()?->can('consent.view'), 403);

        return view('cms-core::admin.consent.service-show', ['service' => $service->load('category', 'technologies'), 'categories' => ConsentCategory::query()->orderBy('sort_order')->get()]);
    }

    public function update(UpdateConsentServiceRequest $request, ConsentService $service, ConsentManagerContract $manager)
    {
        $service->update($request->safe()->merge([
            'requires_consent' => $request->boolean('requires_consent'),
        ])->all());
        $manager->forgetCache();

        return redirect()->route('admin.consent.services.show', $service)->with('status', 'Serviço atualizado.');
    }
}
