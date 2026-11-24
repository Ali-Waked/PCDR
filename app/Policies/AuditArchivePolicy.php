<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class AuditArchivePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('audit_archives.view');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function download(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('audit_archives.download');
    }

    public function manage(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('audit_archives.manage');
    }

    public function cleanup(User $user): bool
    {
        return $user->isPlatformOwner() || $user->can('audit_archives.cleanup');
    }
}
