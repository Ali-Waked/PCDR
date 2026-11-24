<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use App\Support\RolePermissionInput;
use Illuminate\Support\Facades\DB;

final class RbacManagementService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RbacService $rbac,
    ) {}

    /** @param array<int, int> $permissionIds */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $allowedIds = RolePermissionInput::supportedIds();
        $submitted = RolePermissionInput::normalize($permissionIds);
        $selected = array_values(array_intersect($submitted, $allowedIds));

        // Historical or hidden assignments are never silently removed by the
        // dashboard. They remain attached but are not exposed for assignment.
        /** @var list<int> $preserved */
        $preserved = $role->permissions()
            ->whereNotIn('permissions.key', PermissionCatalog::supportedKeys())
            ->pluck('permissions.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $ids = array_values(array_unique(array_merge($selected, $preserved)));
        $permissions = Permission::whereIn('id', $ids)->get();
        $before = ['permissions' => $role->permissions()->pluck('permissions.key')->all()];

        DB::transaction(function () use ($role, $permissions, $before): void {
            $role->permissions()->sync($permissions->pluck('id')->all());
            $this->rbac->invalidate();
            $this->audit->record('role.permissions_changed', 'roles', (string) $role->id, $before, [
                'permissions' => $permissions->pluck('key')->all(),
            ]);
        });
    }

    /** @param array<int, array{resource_key: string, scope_type: string, scope_value?: mixed}> $scopes */
    public function syncRoleScopes(Role $role, array $scopes): void
    {
        $before = $role->scopes()->get()->map(fn ($scope) => [
            'resource_key' => $scope->resource_key,
            'scope_type' => $scope->scope_type,
        ])->all();

        DB::transaction(function () use ($role, $scopes, $before): void {
            $role->scopes()->delete();

            foreach ($scopes as $scope) {
                if (! in_array($scope['scope_type'], ['all', 'self', 'assigned'], true)) {
                    continue;
                }

                $role->scopes()->create([
                    'resource_key' => $scope['resource_key'],
                    'scope_type' => $scope['scope_type'],
                    'scope_value' => $scope['scope_value'] ?? [],
                ]);
            }

            $this->rbac->invalidate();
            $this->audit->record('role.scopes_changed', 'roles', (string) $role->id, $before, [
                'scopes' => collect($scopes)->map(fn (array $scope) => [
                    'resource_key' => $scope['resource_key'],
                    'scope_type' => $scope['scope_type'],
                ])->all(),
            ]);
        });
    }
}
