<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Code-owned dashboard permission catalog.
 *
 * The catalog is derived from config/permissions.php. Only entries explicitly
 * marked `dashboard_supported` are exposed to the administration UI or accepted
 * for role assignment. Historical, reserved, internal, and hidden permissions
 * remain in the code catalog for documentation and authorization compatibility,
 * but are never presented as assignable dashboard capabilities.
 */
final class PermissionCatalog
{
    /** @var array<string, array{en: string, ar: string}> */
    private const CATEGORY_LABELS = [
        'identity_access' => ['en' => 'Identity and access', 'ar' => 'الهوية والوصول'],
        'platform_administration' => ['en' => 'Platform administration', 'ar' => 'إدارة المنصة'],
        'website' => ['en' => 'Website and content', 'ar' => 'الموقع والمحتوى'],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        /** @var array<string, array<string, mixed>> $catalog */
        $catalog = config('permissions', []);

        return $catalog;
    }

    /** @return array<string, array<string, mixed>> */
    public static function supported(): array
    {
        return array_filter(
            self::all(),
            static fn (array $definition): bool => ($definition['dashboard_supported'] ?? false) === true,
        );
    }

    /** @return array<string, array<string, mixed>> */
    public static function excluded(): array
    {
        return array_filter(
            self::all(),
            static fn (array $definition): bool => ($definition['dashboard_supported'] ?? false) !== true,
        );
    }

    /** @return list<string> */
    public static function supportedKeys(): array
    {
        return array_keys(self::supported());
    }

    public static function isSupported(string $key): bool
    {
        return array_key_exists($key, self::supported());
    }

    /** @return array<string, array{en: string, ar: string}> */
    public static function categoryLabels(): array
    {
        return self::CATEGORY_LABELS;
    }

    /**
     * Group supported permissions for the read-only catalog and role editor.
     *
     * @param  array<string, mixed>  $recordsByKey  permission database rows keyed by permission key
     * @return list<array{key: string, label: array{en: string, ar: string}, items: list<array<string, mixed>>}>
     */
    public static function groups(array $recordsByKey = []): array
    {
        $grouped = [];

        foreach (self::supported() as $key => $definition) {
            $category = (string) ($definition['category'] ?? 'general');
            $labels = self::CATEGORY_LABELS[$category] ?? [
                'en' => 'General',
                'ar' => 'عام',
            ];
            $record = $recordsByKey[$key] ?? null;
            $id = is_object($record) ? ($record->id ?? null) : (is_array($record) ? ($record['id'] ?? null) : null);

            $grouped[$category]['key'] = $category;
            $grouped[$category]['label'] = $labels;
            $grouped[$category]['items'][] = [
                'id' => $id === null ? null : (int) $id,
                'key' => $key,
                'name' => (string) ($definition['label'] ?? $key),
                'name_ar' => (string) ($definition['label_ar'] ?? ($definition['label'] ?? $key)),
                'description' => (string) ($definition['description'] ?? ''),
                'description_ar' => (string) ($definition['description_ar'] ?? ''),
                'module' => (string) ($definition['module'] ?? 'general'),
                'protected' => (bool) ($definition['protected'] ?? false),
                'assignable' => $id !== null,
            ];
        }

        return array_values(array_map(
            static fn (array $group): array => [
                'key' => (string) $group['key'],
                'label' => $group['label'],
                'items' => $group['items'],
            ],
            $grouped,
        ));
    }
}
