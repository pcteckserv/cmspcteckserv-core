<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Plugins\PluginManager;

class PluginsController extends Controller
{
    public function index(PluginManager $plugins): View
    {
        abort_unless(auth()->user()?->can('plugins.view'), 403);

        return view('cms-core::admin.plugins.index', [
            'plugins' => $plugins->all(),
            'pluginsEnabled' => config('cms-plugins.enabled', true),
        ]);
    }

    public function enable(string $plugin, PluginManager $plugins): RedirectResponse
    {
        abort_unless(auth()->user()?->can('plugins.manage'), 403);

        if (! config('cms-plugins.enabled', true)) {
            return redirect()
                ->route('admin.plugins.index')
                ->with('cms_plugin_error', 'A gestão de plugins está desativada.');
        }

        $plugins->enable($plugin);

        return redirect()
            ->route('admin.plugins.index')
            ->with('cms_plugin_success', 'Plugin ativado com sucesso.');
    }

    public function disable(string $plugin, PluginManager $plugins): RedirectResponse
    {
        abort_unless(auth()->user()?->can('plugins.manage'), 403);

        if (! config('cms-plugins.enabled', true)) {
            return redirect()
                ->route('admin.plugins.index')
                ->with('cms_plugin_error', 'A gestão de plugins está desativada.');
        }

        $plugins->disable($plugin);

        return redirect()
            ->route('admin.plugins.index')
            ->with('cms_plugin_success', 'Plugin desativado com sucesso.');
    }
}
