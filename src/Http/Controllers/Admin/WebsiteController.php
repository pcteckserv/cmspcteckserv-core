<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Pcteckserv\CmsCore\Support\Website;

class WebsiteController
{
    public function edit(Request $request, Website $website)
    {
        Gate::authorize('core.site-options.view');
        $section = $request->route('section');
        abort_unless(in_array($section, ['accounts', 'policies', 'menus', 'style'], true), 404);

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
        } elseif ($section === 'style') {
            $data = $request->validate(['css' => 'nullable|string|max:100000']);
            $website->save('css', $data['css'] ?? '');
        } elseif ($section === 'menus') {
            $data = $request->validate(['name' => 'required|string|max:100', 'slug' => 'required|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|max:100', 'original_slug' => 'nullable|string|max:100', 'location' => 'nullable|in:header,footer,custom', 'items' => 'nullable|array|max:100', 'items.*.id' => 'required|string|regex:/^[a-zA-Z0-9_-]+$/|distinct|max:50', 'items.*.label' => 'required|string|max:100', 'items.*.url' => ['required', 'string', 'max:2000', function ($attribute, $value, $fail) {
                if (! preg_match('~^(?:https?://[^\s]+|/(?!/)[^\s\\\\]*|#[a-zA-Z0-9_-]+)$~', $value) || preg_match('/[\x00-\x20\\\\]/', $value)) {
                    $fail('Indique um endereço http(s), um caminho do site ou uma âncora.');
                }
            }], 'items.*.parent' => 'nullable|string|max:50', 'items.*.position' => 'required|integer|min:0|max:10000', 'items.*.new_tab' => 'sometimes|boolean']);
            $items = $data['items'] ?? [];
            $byId = collect($items)->keyBy('id');
            foreach ($items as $item) {
                $seen = [$item['id']];
                $parent = $item['parent'] ?? '';
                while ($parent !== '' && $parent !== null) {
                    if (in_array($parent, $seen, true) || ! isset($byId[$parent])) {
                        throw ValidationException::withMessages(['items' => 'Os superiores devem pertencer ao menu e não podem formar ciclos.']);
                    }
                    $seen[] = $parent;
                    $parent = $byId[$parent]['parent'] ?? '';
                }
            }
            $menus = $website->menus();
            if (isset($menus[$data['slug']]) && $data['slug'] !== ($data['original_slug'] ?? '')) {
                throw ValidationException::withMessages(['slug' => 'Já existe um menu com este identificador.']);
            }
            unset($menus[$data['original_slug'] ?? '']);
            $menus[$data['slug']] = ['name' => $data['name'], 'slug' => $data['slug'], 'location' => $data['location'] ?? 'custom', 'items' => $items];
            $website->save('menus', $menus);

            return redirect()->route('admin.website.edit', ['section' => 'menus', 'menu' => $data['slug']])->with('status', 'Menu guardado.');
        } else {
            abort(404);
        }

        return back()->with('status', 'Configuração guardada.');
    }

    public function destroy(string $slug, Website $website)
    {
        Gate::authorize('core.site-options.update');
        $menus = $website->menus();
        abort_unless(isset($menus[$slug]), 404);
        unset($menus[$slug]);
        $website->save('menus', $menus);

        return redirect()->route('admin.website.edit', 'menus')->with('status', 'Menu eliminado.');
    }
}
