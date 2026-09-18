<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Updates\PackageUpdater;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;
use Throwable;

class UpdatesController extends Controller
{
    public function index(PackageVersionRegistry $registry, UpdateStatusRepository $statuses, PluginCatalog $plugins): View
    {
        abort_unless(auth()->user()?->can('updates.view'), 403);

        $packages = config('cms-core.updates.enabled', true)
            ? $registry->checkRemoteUpdates()
            : $registry->sync();

        return view('cms-core::admin.updates.index', [
            'packages' => $packages,
            'updatesEnabled' => config('cms-core.updates.enabled', true),
            'channel' => config('cms-core.updates.channel', 'stable'),
            'statuses' => $statuses->all(),
            'pluginPackages' => $plugins->packages(),
        ]);
    }

    public function update(
        string $package,
        UpdateStatusRepository $statuses,
        PluginCatalog $plugins,
        PackageUpdater $updater,
        PackageVersionRegistry $registry,
    ): RedirectResponse
    {
        abort_unless(auth()->user()?->can('updates.manage'), 403);

        if (! config('cms-core.updates.enabled', true)) {
            return redirect()
                ->route('admin.updates.index')
                ->with('cms_update_error', 'O sistema de atualizações está desativado.');
        }

        $allowedPackages = collect(config('cms-core.updates.packages', []))
            ->merge($plugins->packages())
            ->unique()
            ->values()
            ->all();

        if (! in_array($package, $allowedPackages, true)) {
            return redirect()
                ->route('admin.updates.index')
                ->with('cms_update_error', 'Package CMS inválido.');
        }

        $status = $statuses->get($package);

        if (in_array($status['state'] ?? null, ['queued', 'running'], true)) {
            return redirect()
                ->route('admin.updates.index')
                ->with('cms_update_error', 'Já existe uma atualização deste package em curso.');
        }

        $userId = auth()->id();

        $statuses->markRunning($package, $userId);

        try {
            $result = $updater->update($package);
            $registry->checkRemoteUpdates();

            $statuses->markFinished($package, $result, $userId);
        } catch (Throwable $exception) {
            $statuses->markFailed($package, 'A atualização falhou: '.$exception->getMessage(), $userId);

            return redirect()
                ->route('admin.updates.index')
                ->with('cms_update_error', 'A atualização falhou: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.updates.index')
            ->with(
                $result->successful ? 'cms_update_success' : 'cms_update_error',
                $result->message,
            );
    }
}
