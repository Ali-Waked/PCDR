<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Inertia\Inertia;
use Inertia\Response;

final class AccessController extends Controller
{
    public function index(): Response
    {
        $supportedKeys = PermissionCatalog::supportedKeys();
        $permissionRecords = Permission::query()
            ->whereIn('key', $supportedKeys)
            ->where('is_retired', false)
            ->get()
            ->keyBy('key');

        $roles = Role::query()
            ->withCount([
                'permissions' => static fn ($query) => $query
                    ->whereIn('permissions.key', $supportedKeys)
                    ->where('permissions.is_retired', false),
                'users',
            ])
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get()
            ->map(static function (Role $role) use ($permissionRecords): array {
                $assignedIds = $role->permissions()
                    ->whereIn('permissions.key', array_keys($permissionRecords->all()))
                    ->where('permissions.is_retired', false)
                    ->pluck('permissions.id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all();

                $members = $role->users()
                    ->get(['users.id', 'users.name', 'users.email'])
                    ->map(static fn ($user): array => [
                        'id' => (int) $user->id,
                        'name' => (string) $user->name,
                        'email' => (string) $user->email,
                    ])
                    ->all();

                return [
                    'id' => (int) $role->id,
                    'key' => (string) $role->key,
                    'name' => (string) $role->name,
                    'description' => $role->description,
                    'is_system' => (bool) $role->is_system,
                    'permissions_count' => (int) $role->permissions_count,
                    'users_count' => (int) $role->users_count,
                    'permissionIds' => $assignedIds,
                    'users' => $members,
                ];
            })
            ->all();

        return Inertia::render('Admin/Access/Index', [
            'roles' => $roles,
            'catalog' => PermissionCatalog::groups($permissionRecords->all()),
            'stats' => [
                'total_roles' => Role::count(),
                'system_roles' => Role::where('is_system', true)->count(),
                'custom_roles' => Role::where('is_system', false)->count(),
                'available_permissions' => count($supportedKeys),
            ],
        ]);
    }
}
