<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Pcteckserv\CmsCore\Jobs\UpdatePackageJob;
use Pcteckserv\CmsCore\Plugins\PluginCatalog;
use Pcteckserv\CmsCore\Updates\PackageVersionRegistry;
use Pcteckserv\CmsCore\Updates\UpdateStatusRepository;

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
            'queueConnection' => config('cms-core.updates.queue_connection') ?: config('queue.default', 'sync'),
            'pluginPackages' => $plugins->packages(),
        ]);
    }

    public function update(string $package, UpdateStatusRepository $statuses, PluginCatalog $plugins): RedirectResponse
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

        $connection = config('cms-core.updates.queue_connection') ?: config('queue.default', 'sync');

        if ($connection === 'sync') {
            return redirect()
                ->route('admin.updates.index')
                ->with('cms_update_error', 'Configure uma queue assíncrona antes de executar atualizações em segundo plano.');
        }

        $statuses->markQueued($package, auth()->id());

        $job = new UpdatePackageJob($package, auth()->id());

        if (is_string($connection) && $connection !== '') {
            $job->onConnection($connection);
        }

        dispatch($job);

        return redirect()
            ->route('admin.updates.index')
            ->with('cms_update_success', 'Atualização colocada na fila. Pode continuar a utilizar o CMS enquanto é processada.');
    }
}
