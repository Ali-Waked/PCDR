<?php

declare(strict_types=1);

namespace App\Support;

final class Locale
{
    public static function isValid(?string $locale): bool
    {
        return in_array($locale, config('website.locales', ['ar', 'en']), true);
    }

    public static function sanitize(?string $locale): string
    {
        return self::isValid($locale) ? $locale : (string) config('website.default_locale', 'ar');
    }

    public static function direction(?string $locale = null): string
    {
        return self::sanitize($locale) === 'ar' ? 'rtl' : 'ltr';
    }

    public static function alternate(string $locale): string
    {
        return $locale === 'ar' ? 'en' : 'ar';
    }

    /** @return array{ar: string, en: string, x-default: string} */
    public static function alternates(string $path): array
    {
        $path = ltrim($path, '/');
        // Controllers pass locale-prefixed paths; normalize before swapping locale.
        $path = trim((string) preg_replace('#^(ar|en)(?:/|$)#', '', $path), '/');

        return [
            'ar' => url('/ar'.($path ? '/'.$path : '')),
            'en' => url('/en'.($path ? '/'.$path : '')),
            'x-default' => url('/ar'.($path ? '/'.$path : '')),
        ];
    }
}
