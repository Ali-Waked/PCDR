<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('settings.view');
    }

    public function update(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('settings.update');
    }
}
