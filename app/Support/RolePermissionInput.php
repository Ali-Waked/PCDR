<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Shared server-side guard for role permission assignment payloads.
 *
 * The dashboard never trusts submitted permission IDs. Every ID is resolved
 * against the code-owned supported catalog before a role can be created,
 * updated, or synchronized.
 */
final class RolePermissionInput
{
    /** @param mixed $value */
    public static function normalize($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = array_map(
            static fn (mixed $item): int => is_numeric($item) ? (int) $item : -1,
            $value,
        );

        return array_values(array_unique($ids));
    }

    /** @return list<int> */
    public static function supportedIds(): array
    {
        return Permission::query()
            ->whereIn('key', PermissionCatalog::supportedKeys())
            ->where('is_retired', false)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /** @return array<string, mixed> */
    public static function rules(bool $required = false): array
    {
        return [
            'permissions' => $required ? ['required', 'array'] : ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::in(self::supportedIds())],
        ];
    }

    /** @param array<int, int> $ids */
    public static function touchesProtected(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        return Permission::query()
            ->whereIn('id', $ids)
            ->where('is_protected', true)
            ->exists();
    }

    /**
     * @param  array<int, int>  $ids
     * @param  array<int, int>  $currentIds
     */
    public static function canAssign(?User $user, array $ids, array $currentIds = []): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isPlatformOwner()) {
            return true;
        }

        $changed = array_values(array_unique(array_merge($ids, $currentIds)));

        return ! self::touchesProtected($changed);
    }
}
