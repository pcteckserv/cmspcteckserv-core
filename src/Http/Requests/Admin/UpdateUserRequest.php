<?php

namespace Pcteckserv\CmsCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Pcteckserv\CmsCore\Models\Role;
use Pcteckserv\CmsCore\Services\UserModelResolver;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        if (! $this->user()?->can('core.users.update')) {
            return false;
        }

        return ! (method_exists($target, 'isCmsSuperAdmin') && $target->isCmsSuperAdmin() && ! $this->user()->isCmsSuperAdmin());
    }

    public function rules(UserModelResolver $resolver): array
    {
        $userModel = $resolver->className();
        $table = (new $userModel)->getTable();
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($table, 'email')->ignore($target?->getKey())],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'state' => ['required', Rule::in(config('cms-core.user_states', ['active', 'inactive']))],
            'role_id' => ['nullable', 'integer', Rule::exists('cms_roles', 'id')],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('cms_roles', 'id')],
            'direct_permissions_enabled' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::exists('cms_permissions', 'id')],
        ];
    }

    public function roleIds(): array
    {
        if (! $this->user()?->can('core.users.manage_roles')) {
            return [];
        }

        $roleId = $this->validated('role_id');

        if ($roleId === null) {
            $roleId = $this->validated('roles.0');
        }

        if ($roleId === null) {
            return [];
        }

        $roleIds = [(int) $roleId];

        if ($this->user()?->isCmsSuperAdmin()) {
            return $roleIds;
        }

        return Role::query()
            ->whereIn('id', $roleIds)
            ->where('is_protected', false)
            ->pluck('id')
            ->all();
    }

    public function permissionIds(): array
    {
        if (! $this->user()?->can('core.users.manage_roles') || ! $this->boolean('direct_permissions_enabled')) {
            return [];
        }

        return array_map('intval', $this->validated('permissions', []));
    }
}
