<?php

namespace Pcteckserv\CmsCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Pcteckserv\CmsCore\Http\Requests\Admin\StoreRoleRequest;
use Pcteckserv\CmsCore\Http\Requests\Admin\UpdateRoleRequest;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\PermissionSynchronizer;

class RolesController
{
    public function __construct(private readonly PermissionSynchronizer $permissions)
    {
    }

    public function index(): View
    {
        Gate::authorize('core.roles.view');

        return view('cms-core::admin.roles.index', [
            'roles' => $this->visibleRolesQuery()->withCount('permissions')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('core.roles.create');

        return view('cms-core::admin.roles.create', $this->formData());
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create($request->safe()->only(['name', 'key']));
        $role->permissions()->sync($request->validated('permissions', []));

        return redirect()->route('admin.roles.edit', $role)->with('status', 'Role criada com sucesso.');
    }

    public function edit(Role $role): View
    {
        Gate::authorize('core.roles.update');
        $this->abortIfSuperAdminRole($role);

        return view('cms-core::admin.roles.edit', array_merge($this->formData(), [
            'role' => $role->load('permissions'),
        ]));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->abortIfSuperAdminRole($role);

        if ($role->is_protected && ! $request->user()?->isCmsSuperAdmin()) {
            abort(403);
        }

        $role->update($request->safe()->only(['name', 'key']));
        $role->permissions()->sync($request->validated('permissions', []));

        return redirect()->route('admin.roles.edit', $role)->with('status', 'Role atualizada com sucesso.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('core.roles.delete');
        $this->abortIfSuperAdminRole($role);

        if ($role->is_protected) {
            return back()->withErrors(['role' => 'Não é possível eliminar uma role protegida.']);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role eliminada com sucesso.');
    }

    private function formData(): array
    {
        $this->permissions->sync();

        return [
            'permissionsByGroup' => Permission::query()->orderBy('group')->orderBy('label')->get()->groupBy('group'),
        ];
    }

    private function visibleRolesQuery()
    {
        return Role::query()->where('key', '!=', $this->superAdminRoleKey());
    }

    private function abortIfSuperAdminRole(Role $role): void
    {
        if ($role->key === $this->superAdminRoleKey()) {
            abort(404);
        }
    }

    private function superAdminRoleKey(): string
    {
        return (string) config('cms-core.super_admin_role', 'core.super_admin');
    }
}
