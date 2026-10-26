<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Pcteckserv\CmsCore\Support\Website;

class WebsiteController
{
    public function edit(Request $request, Website $website)
    {
        Gate::authorize('core.site-options.view');
        $section = $request->route('section');
        abort_unless(in_array($section, ['accounts', 'policies'], true), 404);

        return view('cms-core::admin.website.edit', compact('website', 'section'));
    }

    public function update(Request $request, Website $website)
    {
        Gate::authorize('core.site-options.update');
        $section = $request->route('section');
        if ($section === 'accounts') {
            $request->validate(['registration' => 'required|boolean', 'require_account' => 'sometimes|boolean']);
            $website->save('registration', $request->boolean('registration'));
            if ($request->has('require_account')) {
                $website->save('require_account', $request->boolean('require_account'));
            }
        } elseif ($section === 'policies') {
            $data = $request->validate(['policies' => 'required|array:privacy,cookies,terms,refunds', 'policies.*' => 'nullable|string|max:100000']);
            foreach ($data['policies'] as $key => $text) {
                $website->save('policy.'.$key, $text ?? '');
            }
        } else {
            abort(404);
        }

        return back()->with('status', 'Configuração guardada.');
    }
}
