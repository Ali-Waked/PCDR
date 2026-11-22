<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Authorization seam for the CMS.
 *
 * Phase 2 is the sole owner of RBAC. This class never creates roles or
 * permissions; it only forwards a permission key to the first available
 * Phase 2 contract and fails closed when none exists. The email bootstrap is
 * a development-only convenience and is disabled in production.
 */
final class CmsAccess
{
    public function allows(User $user, string $permission): bool
    {
        if (method_exists($user, 'hasPermission')) {
            return (bool) $user->hasPermission($permission);
        }

        if (class_exists(PermissionService::class)) {
            $service = app(PermissionService::class);
            if (method_exists($service, 'allows')) {
                return (bool) $service->allows($user, $permission);
            }
        }

        if (Gate::has($permission)) {
            return Gate::forUser($user)->allows($permission);
        }

        $bootstrap = config('cms.bootstrap.email');
        if ($bootstrap && ! app()->isProduction() && hash_equals($bootstrap, $user->email)) {
            return true;
        }

        return false;
    }

    /** @return list<string> */
    public function permissionsFor(User $user): array
    {
        return array_values(array_filter(
            array_keys((array) config('cms.permissions', [])),
            fn (string $permission) => $this->allows($user, $permission),
        ));
    }
}
