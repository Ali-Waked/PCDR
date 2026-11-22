<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AccessManagementService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RbacService $rbac,
        private readonly SessionInvalidationService $sessions,
    ) {}

    public function setStatus(User $user, string $status): void
    {
        if (! in_array($status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_SUSPENDED], true)) {
            throw new \InvalidArgumentException('Invalid account status.');
        }

        if ($user->isPlatformOwner() && $status !== User::STATUS_ACTIVE && $this->activeOwnerCount() <= 1) {
            throw new \RuntimeException('The last active Platform Owner cannot be deactivated.');
        }

        $before = ['status' => $user->status];

        DB::transaction(function () use ($user, $status, $before): void {
            $user->update(['status' => $status]);
            $user->failed_login_attempts = 0;
            $user->locked_until = null;
            $user->save();

            if ($status !== User::STATUS_ACTIVE) {
                $this->sessions->invalidate($user);
            }

            $this->rbac->invalidate();
            $this->rbac->forgetUser($user);
            $this->audit->record('user.status_changed', 'users', (string) $user->id, $before, ['status' => $status]);
        });
    }

    public function delete(User $user): void
    {
        if ($user->isPlatformOwner()) {
            throw new \RuntimeException('The Platform Owner account cannot be deleted.');
        }

        DB::transaction(function () use ($user): void {
            $this->audit->record('user.deleted', 'users', (string) $user->id, [
                'name' => $user->name,
                'email' => $user->email,
            ]);
            $this->sessions->invalidate($user);
            $user->delete();
            $this->rbac->invalidate();
        });
    }

    /** @param array<int, int> $roleIds */
    public function syncRoles(User $user, array $roleIds, ?User $actor = null): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();
        $before = ['roles' => $user->roles()->pluck('roles.key')->all()];

        DB::transaction(function () use ($user, $roles, $before, $actor): void {
            $user->roles()->sync($roles->mapWithKeys(fn (Role $role) => [
                $role->id => ['assigned_by' => $actor?->id],
            ])->all());

            $this->rbac->invalidate();
            $this->rbac->forgetUser($user);
            $this->audit->record('user.roles_changed', 'users', (string) $user->id, $before, [
                'roles' => $roles->pluck('key')->all(),
            ]);
        });
    }

    private function activeOwnerCount(): int
    {
        return User::where('is_platform_owner', true)
            ->where('status', User::STATUS_ACTIVE)
            ->count();
    }
}
