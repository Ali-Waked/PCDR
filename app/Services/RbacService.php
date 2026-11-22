<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\RoleScope;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class RbacService
{
    private const VERSION_KEY = 'pcdr:rbac:version';

    /** @return Collection<int, string> */
    public function permissionsFor(User $user): Collection
    {
        if ($user->isPlatformOwner()) {
            return collect(array_keys(config('permissions', [])))->push('*')->unique()->values();
        }

        $key = $this->key('permissions', $user);

        return Cache::rememberForever($key, function () use ($user): Collection {
            return $user->roles()
                ->whereNull('roles.deleted_at')
                ->with('permissions')
                ->get()
                ->flatMap(fn (Role $role) => $role->permissions->where('is_retired', false)->pluck('key'))
                ->unique()
                ->values();
        });
    }

    /** @return Collection<int, string> */
    public function rolesFor(User $user): Collection
    {
        if ($user->isPlatformOwner()) {
            return collect(['platform_owner']);
        }

        $key = $this->key('roles', $user);

        return Cache::rememberForever($key, fn () => $user->roles()->pluck('roles.key')->unique()->values());
    }

    /** @return Collection<int, RoleScope> */
    public function scopeRowsFor(User $user): Collection
    {
        if ($user->isPlatformOwner()) {
            return collect();
        }

        return $user->roles()
            ->with('scopes')
            ->get()
            ->flatMap(fn (Role $role) => $role->scopes)
            ->values();
    }

    /**
     * Public-safe scope summary. Scope values and internal identifiers are
     * deliberately never returned.
     *
     * @return array<string, list<array{scope_type: string}>>
     */
    public function publicScopesFor(User $user): array
    {
        if ($user->isPlatformOwner()) {
            return ['*' => [['scope_type' => 'all']]];
        }

        $result = [];

        foreach ($this->scopeRowsFor($user) as $scope) {
            $result[$scope->resource_key][] = ['scope_type' => $scope->scope_type];
        }

        return $result;
    }

    public function has(User $user, string $permission): bool
    {
        if ($user->isPlatformOwner()) {
            return true;
        }

        return $this->permissionsFor($user)->contains($permission);
    }

    public function invalidate(): void
    {
        try {
            Cache::increment(self::VERSION_KEY);
        } catch (\Throwable) {
            Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
        }
    }

    public function forgetUser(User $user): void
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        foreach (['permissions', 'roles'] as $type) {
            Cache::forget("pcdr:rbac:v{$version}:{$type}:{$user->id}");
        }
    }

    private function key(string $type, User $user): string
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        return "pcdr:rbac:v{$version}:{$type}:{$user->id}";
    }
}
