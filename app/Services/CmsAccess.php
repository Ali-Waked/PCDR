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
        // Institutional authority never flows through the CMS seam, including
        // the development-only bootstrap identity below.
        if (app(RbacService::class)->isAuthorityPermission($permission)) {
            return false;
        }

        // Phase 2's RbacService is the authoritative permission contract once
        // the two phases are integrated. Keep the other seams for isolation.
        if (class_exists(RbacService::class) && app(RbacService::class)->has($user, $permission)) {
            return true;
        }

        if (method_exists($user, 'hasPermission')) {
            return (bool) $user->hasPermission($permission);
        }

        if (class_exists(PermissionService::class)) {
            $service = app(PermissionService::class);
            if (method_exists($service, 'allows')) {
                return (bool) $service->allows($user, $permission);
            }
        }

        // Explicit development-only identity, evaluated before generic gates so
        // the isolated local CMS workflow keeps working after integration.
        $bootstrap = config('cms.bootstrap.email');
        if ($bootstrap && ! app()->isProduction() && hash_equals($bootstrap, $user->email)) {
            return true;
        }

        if (Gate::has($permission)) {
            return Gate::forUser($user)->allows($permission);
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
