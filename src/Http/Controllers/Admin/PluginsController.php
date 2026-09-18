<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Http\Requests\Admin\InstallPluginRequest;
use Pcteckserv\CmsCore\Plugins\PluginInstaller;
use Pcteckserv\CmsCore\Plugins\PluginManager;
use Pcteckserv\CmsCore\Plugins\PluginRepository;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PluginsController extends Controller
{
    public function index(PluginManager $plugins, PluginRepository $repository): View
    {
        abort_unless(auth()->user()?->can('plugins.view'), 403);

        try {
            $availablePlugins = $repository->available();
            $repositoryError = null;
        } catch (ProcessFailedException $exception) {
            $availablePlugins = collect();
            $repositoryError = 'Não foi possível atualizar o repositório de plugins. Verifique a ligação e as credenciais de acesso ao GitHub.';
        }

        $installedPlugins = $plugins->all();
        $installedPackages = $installedPlugins->whereNotNull('installed_version')->pluck('package');

        return view('cms-core::admin.plugins.index', [
            'plugins' => $installedPlugins,
            'availablePlugins' => $availablePlugins->reject(fn ($plugin) => $installedPackages->contains($plugin->package))->values(),
            'pluginRepositoryError' => $repositoryError,
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

    public function install(InstallPluginRequest $request, PluginInstaller $installer, PluginRepository $repository): RedirectResponse
    {
        if (! config('cms-plugins.enabled', true)) {
            return redirect()
                ->route('admin.plugins.index')
                ->with('cms_plugin_error', 'A gestão de plugins está desativada.');
        }

        try {
            $plugin = $repository->find($request->validated('plugin'));
        } catch (ProcessFailedException $exception) {
            return redirect()
                ->route('admin.plugins.index')
                ->with('cms_plugin_error', 'Não foi possível atualizar o repositório de plugins. Verifique a ligação e as credenciais de acesso ao GitHub.');
        }

        if ($plugin === null) {
            return redirect()
                ->route('admin.plugins.index')
                ->with('cms_plugin_error', 'O plugin selecionado não existe no repositório configurado.');
        }

        $result = $installer->install($plugin->installData());

        return redirect()
            ->route('admin.plugins.index')
            ->with($result->successful ? 'cms_plugin_success' : 'cms_plugin_error', $result->message);
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

    public function destroy(string $plugin, PluginInstaller $installer): RedirectResponse
    {
        abort_unless(auth()->user()?->can('plugins.manage') && auth()->user()?->can('plugins.install'), 403);

        if (! config('cms-plugins.enabled', true)) {
            return redirect()->route('admin.plugins.index')
                ->with('cms_plugin_error', 'A gestão de plugins está desativada.');
        }

        $result = $installer->uninstall($plugin);

        return redirect()->route('admin.plugins.index')
            ->with($result->successful ? 'cms_plugin_success' : 'cms_plugin_error', $result->message);
    }
}
