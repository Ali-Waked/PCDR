<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Throwable;

final class Phase5Support
{
    public static function reference(string $prefix): string
    {
        // Laravel 13 returns a Ulid value object; cast before uppercasing.
        return strtoupper($prefix).'-'.strtoupper((string) Str::ulid());
    }

    public static function clean(string $value, int $max = 2000): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return Str::limit(trim($value), $max, '');
    }

    /** @param array<string, string|null> $values */
    public static function localized(array $values, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return self::clean($values[$locale] ?? $values['en'] ?? $values['ar'] ?? '', 255);
    }

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    public static function decode(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Produce a short, non-sensitive diagnostic code for logs and execution
     * history. Exception messages are intentionally not persisted.
     */
    public static function errorCode(Throwable $exception): string
    {
        return 'ERR_'.strtoupper(Str::snake(class_basename($exception)));
    }

    /** @param list<string> $values @return list<string> */
    public static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (string $value): string => trim($value), $values),
            static fn (string $value): bool => $value !== '',
        )));
    }
}
