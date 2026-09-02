<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin\Queues;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use Pcteckserv\CmsCore\Services\Queues\QueueMonitor;
use Throwable;

class QueueDashboardController extends Controller
{
    public function __invoke(QueueMonitor $monitor)
    {
        abort_unless(auth()->user()?->can('queues.view'), 403);

        return view('cms-core::admin.queues.dashboard', $monitor->overview());
    }

    public function restart(ActivityLoggerContract $activityLogger): RedirectResponse
    {
        abort_unless(auth()->user()?->can('queues.manage'), 403);

        return $this->runCommand('queue:restart', [], 'Workers sinalizados para reiniciar.', $activityLogger);
    }

    public function workOnce(ActivityLoggerContract $activityLogger): RedirectResponse
    {
        abort_unless(auth()->user()?->can('queues.manage'), 403);

        return $this->runCommand('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
            '--tries' => 1,
            '--timeout' => (int) config('cms-core.queues.work_once_timeout', 900),
        ], 'Foi processado um ciclo da queue.', $activityLogger);
    }

    public function retry(string $id, ActivityLoggerContract $activityLogger): RedirectResponse
    {
        abort_unless(auth()->user()?->can('queues.manage'), 403);

        return $this->runCommand('queue:retry', ['id' => [$id]], 'Job falhado reenviado para a queue.', $activityLogger);
    }

    public function retryAll(ActivityLoggerContract $activityLogger): RedirectResponse
    {
        abort_unless(auth()->user()?->can('queues.manage'), 403);

        return $this->runCommand('queue:retry', ['id' => ['all']], 'Jobs falhados reenviados para a queue.', $activityLogger);
    }

    public function forget(string $id, ActivityLoggerContract $activityLogger): RedirectResponse
    {
        abort_unless(auth()->user()?->can('queues.manage'), 403);

        return $this->runCommand('queue:forget', ['id' => $id], 'Job falhado removido.', $activityLogger);
    }

    private function runCommand(string $signature, array $parameters, string $successMessage, ActivityLoggerContract $activityLogger): RedirectResponse
    {
        try {
            $exitCode = Artisan::call($signature, $parameters);
            $output = trim(Artisan::output());
        } catch (Throwable $exception) {
            return back()->with('queue_error', 'A operação falhou: '.$exception->getMessage());
        }

        $activityLogger->log('queues.command', 'Queues', 'Comando de queue executado.', null, [
            'signature' => $signature,
            'parameters' => $parameters,
            'exit_code' => $exitCode,
        ]);

        if ($exitCode !== 0) {
            return back()
                ->with('queue_error', "O comando {$signature} terminou com código {$exitCode}.")
                ->with('queue_output', $output);
        }

        return back()
            ->with('queue_success', $successMessage)
            ->with('queue_output', $output ?: 'Operação concluída sem output.');
    }
}
