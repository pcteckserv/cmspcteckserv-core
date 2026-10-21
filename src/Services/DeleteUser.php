<?php

namespace Pcteckserv\CmsCore\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Pcteckserv\CmsCore\ActivityLog\Contracts\ActivityLoggerContract;
use RuntimeException;

class DeleteUser
{
    public function __construct(private readonly ActivityLoggerContract $activityLogger)
    {
    }

    public function handle(Model $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->cmsRoles()->detach();
            $user->cmsPermissions()->detach();
            $user->cmsState()->delete();

            $deleted = in_array(SoftDeletes::class, class_uses_recursive($user), true)
                ? $user->forceDelete()
                : $user->delete();

            if (! $deleted) {
                throw new RuntimeException('Não foi possível eliminar o utilizador.');
            }

            $this->activityLogger->log(
                action: 'user.permanently_deleted',
                category: 'users',
                description: 'Utilizador eliminado definitivamente.',
                subject: $user,
            );
        });
    }
}
