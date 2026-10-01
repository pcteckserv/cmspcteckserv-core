<?php

namespace Pcteckserv\CmsCore\Services;

use Illuminate\Support\Collection;
use Pcteckserv\CmsCore\Models\Permission;
use Pcteckserv\CmsCore\Support\Permissions\PermissionRegistry;

class PermissionSynchronizer
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    public function sync(): int
    {
        $count = 0;

        foreach ($this->registry->all() as $definition) {
            Permission::query()->updateOrCreate(
                ['key' => $definition->key],
                [
                    'label' => $definition->label,
                    'group' => $definition->group,
                    'description' => $definition->description,
                ],
            );

            $count++;
        }

        return $count;
    }

    /** @return Collection<int, Permission> */
    public function registeredPermissions(): Collection
    {
        $this->sync();

        $permissions = Permission::query()
            ->whereIn('key', array_keys($this->registry->all()))
            ->orderBy('group')
            ->orderBy('label')
            ->get();

        $pluginGroups = collect($this->registry->all())
            ->filter(fn ($definition): bool => $definition->plugin !== null)
            ->pluck('group')
            ->unique()
            ->flip();
        $groups = $permissions->groupBy('group');
        $coreGroups = $groups->reject(fn ($groupPermissions, string $group): bool => $pluginGroups->has($group))->sortKeys();
        $registeredPluginGroups = $groups->filter(fn ($groupPermissions, string $group): bool => $pluginGroups->has($group))->sortKeys();

        return $coreGroups->concat($registeredPluginGroups)->flatten(1)->values();
    }

    /**
     * @param  array<int, int|string>  $requestedIds
     * @param  array<int, int|string>  $existingIds
     * @return array<int, int|string>
     */
    public function preservingUnregistered(array $requestedIds, array $existingIds): array
    {
        if ($existingIds === []) {
            return array_values(array_unique($requestedIds));
        }

        $query = Permission::query()->whereIn('id', $existingIds);
        $registeredKeys = array_keys($this->registry->all());

        if ($registeredKeys !== []) {
            $query->whereNotIn('key', $registeredKeys);
        }

        return array_values(array_unique([
            ...$requestedIds,
            ...$query->pluck('id')->all(),
        ]));
    }
}
