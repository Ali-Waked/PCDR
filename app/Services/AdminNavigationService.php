<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

final class AdminNavigationService
{
    public function __construct(private readonly RbacService $rbac) {}

    /**
     * Resolve every visible administrative destination on the server. The
     * frontend receives the result as presentation data only.
     *
     * @return list<array{key: string, label: string, items: list<array{key: string, label: string, path: string, permission: ?string}>}>
     */
    public function visibleFor(User $user, string $locale): array
    {
        $groups = [];

        /** @var list<array{key: string, label: string, feature?: string, items: list<array{key: string, label: string, path: string, permission: ?string}>}> $configured */
        $configured = config('admin_navigation.groups', []);

        foreach ($configured as $group) {
            $groupKey = (string) ($group['key'] ?? '');
            $featureKey = (string) ($group['feature'] ?? $groupKey);
            $featureState = config("dashboard_features.features.{$featureKey}.state");

            if ($featureState !== null && $featureState !== 'enabled') {
                continue;
            }

            $items = [];

            foreach ($group['items'] as $item) {
                $permission = $item['permission'];

                if ($permission !== null && ! $this->rbac->has($user, $permission)) {
                    continue;
                }

                $items[] = [
                    'key' => $item['key'],
                    'label' => $this->label($item['label'], $locale),
                    'path' => $item['path'],
                    'permission' => $permission,
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => $group['key'],
                'label' => $this->label($group['label'], $locale),
                'items' => $items,
            ];
        }

        return $groups;
    }

    private function label(string $key, string $locale): string
    {
        $translation = __("ui.admin_nav.{$key}", [], $locale);

        if ($translation !== "ui.admin_nav.{$key}") {
            return (string) $translation;
        }

        return Str::headline(str_replace('_', ' ', $key));
    }
}
