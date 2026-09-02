@php
    $selectedPermissionIds = collect(old($fieldName, $selectedPermissionIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="cms-permissions-panel" data-cms-permissions-panel>
    <div class="cms-permissions-panel__toolbar">
        <div>
            <h2 class="h5 mb-1">{{ $title }}</h2>
            <div class="text-secondary small">{{ $permissionsByGroup->flatten()->count() }} permissões disponíveis</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-primary" type="button" data-cms-permissions-select-all>Selecionar tudo</button>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-cms-permissions-clear>Limpar</button>
        </div>
    </div>

    <div class="cms-permissions-grid">
        @foreach ($permissionsByGroup as $group => $permissions)
            <section class="cms-permission-group" data-cms-permission-group>
                <div class="cms-permission-group__header">
                    <div>
                        <h3>{{ $group }}</h3>
                        <span>{{ $permissions->count() }} permissões</span>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ações de {{ $group }}">
                        <button class="btn btn-outline-primary" type="button" data-cms-permission-group-select>Selecionar</button>
                        <button class="btn btn-outline-secondary" type="button" data-cms-permission-group-clear>Limpar</button>
                    </div>
                </div>

                <div class="cms-permission-group__body">
                    @foreach ($permissions as $permission)
                        <label class="cms-permission-option" for="{{ $fieldName }}-{{ $permission->id }}">
                            <input
                                class="form-check-input"
                                id="{{ $fieldName }}-{{ $permission->id }}"
                                type="checkbox"
                                name="{{ $fieldName }}[]"
                                value="{{ $permission->id }}"
                                @checked(in_array($permission->id, $selectedPermissionIds, true))
                            >
                            <span>{{ $permission->label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
