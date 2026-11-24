<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Services\AuditService;
use App\Services\RbacManagementService;
use App\Support\RolePermissionInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RoleController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RbacManagementService $rbacManagement,
    ) {}

    public function index(string $locale): RedirectResponse
    {
        return redirect()->route('admin.access.index', ['locale' => $locale]);
    }

    public function create(string $locale): RedirectResponse
    {
        return redirect()->route('admin.access.index', ['locale' => $locale, 'create' => 1]);
    }

    public function edit(string $locale, Role $role): RedirectResponse
    {
        return redirect()->route('admin.access.index', [
            'locale' => $locale,
            'role' => $role->id,
            'edit' => 1,
        ]);
    }

    public function store(string $locale, StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if ($request->has('permissions')) {
            $this->rbacManagement->syncRolePermissions(
                $role,
                RolePermissionInput::normalize($data['permissions'] ?? []),
            );
        }

        $this->audit->record('role.created', 'roles', (string) $role->id, null, ['key' => $role->key]);

        return redirect()->route('admin.access.index', ['locale' => $locale])
            ->with('success', __('ui.role_created'));
    }

    public function update(string $locale, UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        $before = ['key' => $role->key, 'name' => $role->name];

        if ($role->is_system && $data['key'] !== $role->key) {
            abort(422, __('ui.system_role_key_locked'));
        }

        $role->update([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if ($request->has('permissions')) {
            $this->rbacManagement->syncRolePermissions(
                $role,
                RolePermissionInput::normalize($data['permissions'] ?? []),
            );
        }

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

        return redirect()->route('admin.access.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.role_deleted'));
    }

    public function syncPermissions(string $locale, SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->rbacManagement->syncRolePermissions($role, $request->input('permissions', []));

        return back()->with('success', __('ui.permissions_updated'));
    }
}
