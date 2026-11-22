<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUserRolesRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessManagementService;
use App\Services\AuditService;
use App\Services\RbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function __construct(
        private readonly AccessManagementService $access,
        private readonly AuditService $audit,
        private readonly RbacService $rbac,
    ) {}

    public function index(string $locale, Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->with('roles')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'locale' => $user->locale,
                'is_platform_owner' => $user->isPlatformOwner(),
                'roles' => $user->roles->pluck('key')->all(),
                'last_login_at' => $user->last_login_at?->toISOString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(string $locale, User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'target' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'locale' => $user->locale,
                'is_platform_owner' => $user->isPlatformOwner(),
                'roles' => $user->roles()->get(['roles.id', 'roles.key', 'roles.name'])->all(),
            ],
            'availableRoles' => Role::orderBy('name')->get(['id', 'key', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'availableRoles' => Role::orderBy('name')->get(['id', 'key', 'name']),
        ]);
    }

    public function store(string $locale, StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
            'locale' => $data['locale'],
            'email_verified_at' => now(),
        ]);

        if (! empty($data['roles'])) {
            $this->access->syncRoles($user, $data['roles'], $request->user());
        }

        $this->rbac->invalidate();
        $this->audit->record('user.created', 'users', (string) $user->id, null, [
            'name' => $user->name,
            'status' => $user->status,
        ]);

        return redirect()->route('admin.users.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.user_created'));
    }

    public function edit(string $locale, User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'target' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'locale' => $user->locale,
                'roleIds' => $user->roles()->pluck('roles.id')->all(),
            ],
            'availableRoles' => Role::orderBy('name')->get(['id', 'key', 'name']),
        ]);
    }

    public function update(string $locale, UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $before = ['name' => $user->name, 'email' => $user->email, 'status' => $user->status];

        if ($data['status'] !== $user->status) {
            $this->access->setStatus($user, $data['status']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'locale' => $data['locale'],
        ]);

        $this->audit->record('user.updated', 'users', (string) $user->id, $before, [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
        ]);

        return redirect()->route('admin.users.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.user_updated'));
    }

    public function destroy(string $locale, Request $request, User $user): RedirectResponse
    {
        $this->access->delete($user);

        return redirect()->route('admin.users.index', ['locale' => $request->route('locale')])
            ->with('success', __('ui.user_deleted'));
    }

    public function updateStatus(string $locale, UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->access->setStatus($user, $request->string('status')->toString());

        return back()->with('success', __('ui.status_updated'));
    }

    public function syncRoles(string $locale, AssignUserRolesRequest $request, User $user): RedirectResponse
    {
        $this->access->syncRoles($user, $request->input('roles', []), $request->user());

        return back()->with('success', __('ui.roles_updated'));
    }

    public function sendPasswordReset(string $locale, Request $request, User $user): RedirectResponse
    {
        Password::broker()->sendResetLink(['email' => $user->email]);
        $this->audit->record('user.password_reset_requested', 'users', (string) $user->id);

        return back()->with('success', __('ui.reset_link_sent'));
    }
}
