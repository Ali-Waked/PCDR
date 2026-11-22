<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('audit_logs.view');
    }

    public function export(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('audit_logs.export');
    }
}
