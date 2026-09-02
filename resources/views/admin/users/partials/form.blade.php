@if ($errors->any())
    <div class="alert alert-danger">Corrija os campos assinalados.</div>
@endif

<form method="POST" action="{{ $action }}" class="bg-white border rounded p-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Nome</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user?->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user?->email) }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password">Palavra-passe</label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" @if (! $user) required @endif>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="password_confirmation">Confirmar palavra-passe</label>
            <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" @if (! $user) required @endif>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="state">Estado</label>
            <select class="form-select" id="state" name="state">
                @foreach ($states as $state)
                    <option value="{{ $state }}" @selected(old('state', $user?->cmsAccessState() ?? 'active') === $state)>{{ $state === 'active' ? 'Ativo' : 'Inativo' }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @can('core.users.manage_roles')
        <hr>
        <h2 class="h5">Roles</h2>
        <div class="cms-role-options">
            @foreach ($roles as $role)
                <label class="cms-permission-option" for="role-{{ $role->id }}">
                    <input class="form-check-input" id="role-{{ $role->id }}" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $user?->cmsRoles->pluck('id')->all() ?? [])))>
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>

        <hr>
        @php
            $selectedDirectPermissionIds = $user?->cmsPermissions->pluck('id')->all() ?? [];
            $directPermissionsEnabled = old('direct_permissions_enabled', ! empty($selectedDirectPermissionIds));
        @endphp

        <div class="form-check form-switch">
            <input type="hidden" name="direct_permissions_enabled" value="0">
            <input
                class="form-check-input"
                id="direct_permissions_enabled"
                type="checkbox"
                name="direct_permissions_enabled"
                value="1"
                data-cms-direct-permissions-toggle
                @checked((bool) $directPermissionsEnabled)
            >
            <label class="form-check-label fw-semibold" for="direct_permissions_enabled">Ativar permissões diretas</label>
        </div>

        <div data-cms-direct-permissions-panel @if (! $directPermissionsEnabled) hidden @endif>
            @include('cms-core::admin.access.partials.permissions-grid', [
                'title' => 'Permissões diretas',
                'fieldName' => 'permissions',
                'permissionsByGroup' => $permissionsByGroup,
                'selectedPermissionIds' => $selectedDirectPermissionIds,
            ])
        </div>
    @endcan

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancelar</a>
    </div>
</form>
