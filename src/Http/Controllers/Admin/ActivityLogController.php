<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Pcteckserv\CmsCore\Models\ActivityLog;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\UserModelResolver;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(private readonly UserModelResolver $users)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('core.activity-logs.view');

        return view('cms-core::admin.activity-logs.index', [
            'logs' => $this->filteredQuery($request)
                ->with('user')
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString(),
            'users' => $this->users->className()::query()
                ->whereDoesntHave('cmsRoles', fn ($query) => $query->where('key', $this->superAdminRoleKey()))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'categories' => ActivityLog::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'filters' => $request->query(),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        Gate::authorize('core.activity-logs.view');
        abort_if($this->isSuperAdminLog($activityLog), 404);

        return view('cms-core::admin.activity-logs.show', [
            'log' => $activityLog->load(['user', 'subject']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('core.activity-logs.export');

        return response()->streamDownload(function () use ($request): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Data', 'Utilizador', 'Ação', 'Categoria', 'Descrição', 'IP', 'URL']);

            $this->filteredQuery($request)->with('user')->latest()->chunk(500, function ($logs) use ($output): void {
                foreach ($logs as $log) {
                    fputcsv($output, [
                        optional($log->created_at)->timezone(config('cms-core.admin_timezone', 'Europe/Lisbon'))->format('d/m/Y H:i:s'),
                        $log->user?->name ?? 'Sistema',
                        $log->action,
                        $log->category,
                        $log->description,
                        $log->ip_address,
                        $log->url,
                    ]);
                }
            });

            fclose($output);
        }, 'logs-atividade.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(Request $request)
    {
        return ActivityLog::query()
            ->where(fn ($query) => $this->withoutSuperAdminLogs($query))
            ->when(trim((string) $request->query('search')), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when($request->query('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($request->query('category'), fn ($query, $category) => $query->where('category', $category))
            ->when($request->query('action'), fn ($query, $action) => $query->where('action', $action))
            ->when($request->query('ip'), fn ($query, $ip) => $query->where('ip_address', 'like', "%{$ip}%"))
            ->when($request->query('subject_type'), fn ($query, $type) => $query->where('subject_type', 'like', "%{$type}%"))
            ->when($request->query('subject_id'), fn ($query, $id) => $query->where('subject_id', $id))
            ->when($request->query('date_from'), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
    }

    private function withoutSuperAdminLogs($query): void
    {
        $superAdminRoleId = Role::query()->where('key', $this->superAdminRoleKey())->value('id');

        if (! $superAdminRoleId) {
            return;
        }

        $query->whereNotExists(function ($subQuery) use ($superAdminRoleId): void {
            $subQuery->select(DB::raw(1))
                ->from('cms_role_user')
                ->where('role_id', $superAdminRoleId)
                ->whereColumn('cms_role_user.user_id', 'cms_activity_logs.user_id')
                ->whereColumn('cms_role_user.user_type', 'cms_activity_logs.user_type');
        })->whereNotExists(function ($subQuery) use ($superAdminRoleId): void {
            $subQuery->select(DB::raw(1))
                ->from('cms_role_user')
                ->where('role_id', $superAdminRoleId)
                ->whereColumn('cms_role_user.user_id', 'cms_activity_logs.subject_id')
                ->whereColumn('cms_role_user.user_type', 'cms_activity_logs.subject_type');
        });
    }

    private function isSuperAdminLog(ActivityLog $activityLog): bool
    {
        return ! $this->filteredQuery(new Request())->whereKey($activityLog->getKey())->exists();
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 25);

        return in_array($perPage, [25, 50, 100], true) ? $perPage : 25;
    }

    private function superAdminRoleKey(): string
    {
        return (string) config('cms-core.super_admin_role', 'core.super_admin');
    }
}
