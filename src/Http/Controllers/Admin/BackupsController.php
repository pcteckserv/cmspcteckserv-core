<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateBackupDestinationRequest;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateBackupPlanRequest;
use Pcteckserv\CmsCore\Jobs\CreateBackupJob;
use Pcteckserv\CmsCore\Models\BackupAuditLog;
use Pcteckserv\CmsCore\Models\BackupDestination;
use Pcteckserv\CmsCore\Models\BackupPlan;
use Pcteckserv\CmsCore\Models\BackupRun;
use Pcteckserv\CmsCore\Models\BackupSchedulerHeartbeat;
use Pcteckserv\CmsCore\Services\Backups\BackupSchedulerService;
use Pcteckserv\CmsCore\Services\Backups\BackupStorageService;
use Pcteckserv\CmsCore\Services\Backups\BackupVerificationService;
use Throwable;

class BackupsController extends Controller
{
    public function index(BackupSchedulerService $scheduler): View
    {
        Gate::authorize('backups.view');

        $destination = BackupDestination::query()->firstOrCreate(
            ['name' => 'Destino principal'],
            ['disk' => config('cms-backups.local_disk', 'local'), 'protocol' => 'local', 'remote_path' => config('cms-backups.local_path', 'cms-backups')]
        );

        $plan = BackupPlan::query()->firstOrCreate(
            ['name' => 'Backup Completo Diário'],
            [
                'destination_id' => $destination->id,
                'enabled' => false,
                'type' => 'full',
                'frequency' => 'daily',
                'run_at' => '02:00',
                'timezone' => config('cms-backups.default_timezone', config('app.timezone', 'Europe/Lisbon')),
                'included_paths' => config('cms-backups.default_included_paths', []),
                'excluded_paths' => config('cms-backups.default_excluded_paths', []),
                'notification_events' => config('cms-backups.notifications.events', []),
            ]
        );

        if (! $plan->next_run_at) {
            $plan->forceFill(['next_run_at' => $scheduler->nextRunAt($plan)])->save();
        }

        return view('cms-core::admin.backups.index', [
            'destination' => $destination,
            'plan' => $plan->fresh('destination'),
            'runs' => BackupRun::query()->with(['plan', 'destination'])->latest()->paginate(15),
            'lastRun' => BackupRun::query()->latest()->first(),
            'heartbeat' => BackupSchedulerHeartbeat::query()->latest('ran_at')->first(),
        ]);
    }

    public function updateDestination(UpdateBackupDestinationRequest $request, BackupDestination $destination): RedirectResponse
    {
        $destination->fill($request->validated())->save();
        $this->audit('backups.destination.updated', $request);

        return back()->with('cms_backups_success', 'Destino de backups guardado com sucesso.');
    }

    public function testDestination(Request $request, BackupDestination $destination, BackupStorageService $storage): RedirectResponse
    {
        Gate::authorize('backups.configure');

        try {
            $storage->test($destination);
            $destination->forceFill([
                'connection_status' => 'connected',
                'last_tested_at' => now(),
                'last_error' => null,
            ])->save();
            $this->audit('backups.destination.tested', $request);

            return back()->with('cms_backups_success', 'Ligação estabelecida com sucesso.');
        } catch (Throwable $exception) {
            report($exception);
            $destination->forceFill([
                'connection_status' => 'error',
                'last_tested_at' => now(),
                'last_error' => 'Não foi possível validar o destino configurado.',
            ])->save();

            return back()->with('cms_backups_error', 'Não foi possível validar o destino configurado.');
        }
    }

    public function updatePlan(UpdateBackupPlanRequest $request, BackupPlan $plan, BackupSchedulerService $scheduler): RedirectResponse
    {
        $plan->fill($request->validated());
        $plan->next_run_at = $scheduler->nextRunAt($plan);
        $plan->save();
        $this->audit('backups.plan.updated', $request);

        return back()->with('cms_backups_success', 'Plano de backup guardado com sucesso.');
    }

    public function run(Request $request, BackupPlan $plan): RedirectResponse
    {
        Gate::authorize('backups.run');

        $validated = $request->validate([
            'type' => ['required', 'in:database,files,media,full'],
            'storage_mode' => ['required', 'in:local,remote,local_and_remote'],
        ]);

        CreateBackupJob::dispatch($plan->id, $validated['type'], 'manual', $request->user()?->getAuthIdentifier(), $validated['storage_mode']);
        $this->audit('backups.manual.queued', $request);

        return back()->with('cms_backups_success', 'Backup colocado em fila de execução.');
    }

    public function verify(Request $request, BackupRun $run, BackupVerificationService $verification): RedirectResponse
    {
        Gate::authorize('backups.verify');

        try {
            $verification->verify($run);
            $this->audit('backups.run.verified', $request, $run);

            return back()->with('cms_backups_success', 'Backup válido.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('cms_backups_error', 'Backup corrompido ou inacessível.');
        }
    }

    public function destroy(Request $request, BackupRun $run): RedirectResponse
    {
        Gate::authorize('backups.delete');

        if ($run->protected) {
            return back()->with('cms_backups_error', 'Backups protegidos não podem ser eliminados.');
        }

        $run->delete();
        $this->audit('backups.run.deleted', $request, $run);

        return back()->with('cms_backups_success', 'Backup eliminado do histórico.');
    }

    public function testEmail(Request $request, BackupPlan $plan): RedirectResponse
    {
        Gate::authorize('backups.configure');

        $emails = array_values(array_filter($plan->notification_emails ?: [], fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
        if ($emails === []) {
            return back()->with('cms_backups_error', 'Configure pelo menos um email válido para alertas.');
        }

        try {
            Mail::raw('Este email confirma que os alertas de backups do CMS PCTECKSERV estão configurados.', function ($message) use ($emails): void {
                $message->to($emails)->subject('[CMS PCTECKSERV] Teste de alertas de backup');
            });
            $this->audit('backups.alert.test_sent', $request);

            return back()->with('cms_backups_success', 'Email de teste enviado com sucesso.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('cms_backups_error', 'Não foi possível enviar o email de teste.');
        }
    }

    private function audit(string $action, Request $request, ?BackupRun $run = null): void
    {
        BackupAuditLog::query()->create([
            'user_id' => $request->user()?->getAuthIdentifier(),
            'backup_run_id' => $run?->id,
            'action' => $action,
            'result' => 'success',
        ]);
    }
}
