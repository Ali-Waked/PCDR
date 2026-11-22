<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->isPlatformOwner() || $user->can('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isPlatformOwner() || $user->can('users.delete');
    }

    public function changeStatus(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    public function assignRoles(User $user, User $target): bool
    {
        return $user->isPlatformOwner() || $user->can('users.assign_roles');
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->isPlatformOwner() || $user->can('users.reset_password');
    }
}
