<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use App\Services\RbacManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RoleController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RbacManagementService $rbacManagement,
    ) {}

    public function index(): Response
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
            ]);

        return Inertia::render('Admin/Roles/Index', ['roles' => $roles]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Create');
    }

    public function store(string $locale, StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create($data);
        $this->audit->record('role.created', 'roles', (string) $role->id, null, ['key' => $role->key]);

        return redirect()->route('admin.roles.edit', ['locale' => $request->route('locale'), 'role' => $role->id])
            ->with('success', __('ui.role_created'));
    }

    public function edit(string $locale, Role $role): Response
    {
        $permissions = Permission::where('is_retired', false)
            ->orderBy('module')
            ->orderBy('key')
            ->get(['id', 'key', 'name', 'module', 'is_protected'])
            ->groupBy('module')
            ->map(fn ($group, string $module) => ['module' => $module, 'items' => $group->values()->all()])
            ->values()
            ->all();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'permissionIds' => $role->permissions()->pluck('permissions.id')->all(),
                'scopes' => $role->scopes()->get(['resource_key', 'scope_type'])->all(),
                'users' => $role->users()->get(['users.id', 'users.name', 'users.email'])->all(),
            ],
            'permissionGroups' => $permissions,
        ]);
    }

    public function update(string $locale, UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        $before = ['key' => $role->key, 'name' => $role->name];

        if ($role->is_system && $data['key'] !== $role->key) {
            abort(422, __('ui.system_role_key_locked'));
        }

        $role->update($data);
        $this->audit->record('role.updated', 'roles', (string) $role->id, $before, [
            'key' => $role->key,
            'name' => $role->name,
        ]);

        return back()->with('success', __('ui.role_updated'));
    }

    public function destroy(string $locale, Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system) {
            abort(422, __('ui.system_role_locked'));
        }

        $this->audit->record('role.deleted', 'roles', (string) $role->id, ['key' => $role->key]);
        $role->delete();

        return redirect()->route('admin.roles.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.role_deleted'));
    }

    public function syncPermissions(string $locale, SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->rbacManagement->syncRolePermissions($role, $request->input('permissions', []));

        return back()->with('success', __('ui.permissions_updated'));
    }
}
