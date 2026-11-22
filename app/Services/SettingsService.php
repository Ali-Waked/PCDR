<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class SettingsService
{
    private const CACHE_PREFIX = 'pcdr:settings:';

    public function __construct(private readonly AuditService $audit) {}

    /** @return array<string, mixed> */
    public function definitions(): array
    {
        return config('settings.definitions', []);
    }

    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    public function get(string $key, ?string $locale = null): mixed
    {
        $definition = $this->definition($key);

        if ($definition === null) {
            return null;
        }

        $locale ??= app()->getLocale();
        $cacheKey = self::CACHE_PREFIX.$locale;

        $values = Cache::rememberForever($cacheKey, function () use ($locale): array {
            $values = [];

            foreach (Setting::with('translations')->get() as $setting) {
                $values[$setting->group.'.'.$setting->key] = $setting->is_localized
                    ? ($setting->translations->firstWhere('locale', $locale)?->value
                        ?? $setting->translations->firstWhere('locale', 'ar')?->value)
                    : $this->cast($setting->value, $setting->type);
            }

            return $values;
        });

        return $values[$key] ?? $this->cast($definition['default'] ?? null, $definition['type'] ?? 'string');
    }

    /** @return array<string, mixed> */
    public function publicSettings(?string $locale = null): array
    {
        $result = [];

        foreach ($this->definitions() as $key => $definition) {
            if (($definition['public'] ?? false) !== true) {
                continue;
            }

            $result[$key] = $this->get($key, $locale);
        }

        return $result;
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    public function update(array $values): array
    {
        $rules = [];

        foreach ($values as $key => $value) {
            $definition = $this->definition($key);

            if ($definition === null) {
                continue;
            }

            $rules[str_replace('.', '\\.', $key)] = $definition['rules'] ?? ['nullable'];
        }

        Validator::make($values, $rules)->validate();

        $updated = [];

        foreach ($values as $key => $value) {
            $definition = $this->definition($key);

            if ($definition === null) {
                continue;
            }

            $before = $this->get($key);

            $setting = Setting::updateOrCreate(
                ['group' => $definition['group'], 'key' => Str::after($key, '.')],
                [
                    'type' => $definition['type'],
                    'value' => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : ($value === null ? null : (string) $value),
                    'is_public' => (bool) ($definition['public'] ?? false),
                    'is_localized' => false,
                    'is_secret_reference' => false,
                ],
            );

            $this->audit->record(
                'settings.update',
                'settings.'.$definition['group'],
                $key,
                ['value' => $before],
                ['value' => $value],
            );

            $updated[$key] = $setting->id;
        }

        $this->flushCache();

        return $updated;
    }

    public function flushCache(): void
    {
        foreach (['ar', 'en'] as $locale) {
            Cache::forget(self::CACHE_PREFIX.$locale);
        }
    }

    /** @param mixed $value */
    private function cast($value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => is_array($value) ? $value : json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}
