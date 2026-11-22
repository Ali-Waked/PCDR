<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RoleScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ScopeResolver
{
    public function __construct(private readonly RbacService $rbac) {}

    /**
     * Restrict a query to records the user may access for a resource.
     * A user with no scope row receives no records.
     *
     * @param  Builder<mixed>  $query
     * @return bool Whether any positive scope was applied.
     */
    public function apply(Builder $query, string $resourceKey, User $user, string $ownerColumn = 'user_id'): bool
    {
        if ($user->isPlatformOwner()) {
            return true;
        }

        /** @var Collection<int, RoleScope> $scopes */
        $scopes = $this->rbac->scopeRowsFor($user)
            ->where('resource_key', $resourceKey)
            ->values();

        if ($scopes->isEmpty()) {
            $query->whereRaw('1 = 0');

            return false;
        }

        $query->where(function (Builder $inner) use ($scopes, $ownerColumn, $user): void {
            foreach ($scopes as $scope) {
                match ($scope->scope_type) {
                    RoleScope::TYPE_ALL => $inner->orWhereRaw('1 = 1'),
                    RoleScope::TYPE_SELF => $inner->orWhere($ownerColumn, $user->id),
                    RoleScope::TYPE_ASSIGNED => $inner->orWhereIn(
                        $ownerColumn,
                        is_array($scope->scope_value) ? ($scope->scope_value['user_ids'] ?? []) : [],
                    ),
                    default => null,
                };
            }
        });

        return true;
    }
}
