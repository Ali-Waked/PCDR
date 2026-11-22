<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Permission;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PermissionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(string $locale, Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $permissions = Permission::query()
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('key', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->where('is_retired', false)
            ->orderBy('module')
            ->orderBy('key')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
            'filters' => ['search' => $search],
            'catalog' => collect(config('permissions', []))
                ->map(fn (array $definition, string $key) => [
                    'key' => $key,
                    'module' => $definition['module'] ?? 'general',
                    'protected' => (bool) ($definition['protected'] ?? false),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Permissions/Create');
    }

    public function store(string $locale, StorePermissionRequest $request): RedirectResponse
    {
        $permission = Permission::create($request->validated());
        $this->audit->record('permission.created', 'permissions', (string) $permission->id, null, [
            'key' => $permission->key,
        ]);

        return redirect()->route('admin.permissions.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.permission_created'));
    }

    public function edit(string $locale, Permission $permission): Response
    {
        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => [
                'id' => $permission->id,
                'key' => $permission->key,
                'name' => $permission->name,
                'module' => $permission->module,
                'description' => $permission->description,
                'is_protected' => $permission->is_protected,
            ],
        ]);
    }

    public function update(string $locale, UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $data = $request->validated();

        if ($permission->is_protected && $data['key'] !== $permission->key) {
            abort(422, __('ui.protected_permission_key_locked'));
        }

        $before = ['key' => $permission->key, 'name' => $permission->name];
        $permission->update($data);
        $this->audit->record('permission.updated', 'permissions', (string) $permission->id, $before, [
            'key' => $permission->key,
            'name' => $permission->name,
        ]);

        return back()->with('success', __('ui.permission_updated'));
    }

    public function destroy(string $locale, Request $request, Permission $permission): RedirectResponse
    {
        if ($permission->is_protected) {
            abort(422, __('ui.protected_permission_locked'));
        }

        $this->audit->record('permission.deleted', 'permissions', (string) $permission->id, [
            'key' => $permission->key,
        ]);
        $permission->delete();

        return redirect()->route('admin.permissions.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.permission_deleted'));
    }
}
