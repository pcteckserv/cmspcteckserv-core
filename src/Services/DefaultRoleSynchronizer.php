<?php

namespace Pcteckserv\CmsCore\Services;

use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Models\Role;

class DefaultRoleSynchronizer
{
    public function sync(): void
    {
        $admin = Role::query()->firstOrCreate(
            ['key' => 'core.admin'],
            ['name' => 'Administrador', 'is_protected' => false],
        );

        $editor = Role::query()->firstOrCreate(
            ['key' => 'core.editor'],
            ['name' => 'Editor', 'is_protected' => false],
        );

        $admin->permissions()->syncWithoutDetaching(
            Permission::query()->where('key', '!=', 'core.users.manage_roles')->pluck('id')->all(),
        );

        $editor->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('key', ['core.users.view'])->pluck('id')->all(),
        );
    }
}
