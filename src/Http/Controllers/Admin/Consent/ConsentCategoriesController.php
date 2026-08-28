<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Consent;

use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Consent\Contracts\ConsentManagerContract;
use Pcteckserv\CmsCore\Http\Requests\Admin\Consent\UpdateConsentCategoryRequest;
use Pcteckserv\CmsCore\Models\ConsentCategory;

class ConsentCategoriesController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('consent.view'), 403);

        return view('cms-core::admin.consent.categories', ['categories' => ConsentCategory::query()->orderBy('sort_order')->get()]);
    }

    public function update(UpdateConsentCategoryRequest $request, ConsentCategory $category, ConsentManagerContract $manager)
    {
        $category->update($request->safe()->merge([
            'is_active' => $request->boolean('is_active'),
            'is_required' => $request->boolean('is_required'),
        ])->all());
        $manager->forgetCache();

        return redirect()->route('admin.consent.categories.index')->with('status', 'Categoria atualizada.');
    }
}
