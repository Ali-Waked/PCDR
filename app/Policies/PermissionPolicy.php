<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

final class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('permissions.view');
    }

    public function create(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('permissions.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        if ($permission->is_protected) {
            return $user->isPlatformOwner() && $user->can('roles.grant_protected');
        }

        return $user->isPlatformOwner() || $user->can('permissions.update');
    }

    public function delete(User $user, Permission $permission): bool
    {
        if ($permission->is_protected) {
            return false;
        }

        return $user->isPlatformOwner() || $user->can('permissions.delete');
    }
}
