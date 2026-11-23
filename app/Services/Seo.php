<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Support\Locale;

final class Seo
{
    public function __construct(private SiteSettings $settings) {}

    /** @param array<string, mixed> $overrides */
    public function for(
        string $locale,
        string $title,
        ?string $description = null,
        ?string $path = null,
        ?Media $image = null,
        array $overrides = [],
    ): array {
        $description ??= $this->value('seo.default_description', $locale)
            ?: (string) config('website.description.'.$locale, '');
        $shortName = $this->value('organization.short_name', $locale)
            ?: (string) config('website.short_name.'.$locale, 'PCDR');
        $siteTitle = $this->value('seo.site_title', $locale) ?: $shortName;
        $path ??= request()->path();
        $alternates = Locale::alternates($path);
        $ogImage = $this->value('seo.og_image', $locale) ?: $image?->url();

        $data = [
            'title' => $title.' — '.$shortName,
            'description' => mb_strimwidth($description, 0, 320, '…', 'UTF-8'),
            'canonical' => url('/'.ltrim($path, '/')),
            'locale' => $locale,
            'alternates' => $alternates,
            'og' => [
                'type' => 'website',
                'title' => $title,
                'description' => $description,
                'url' => url('/'.ltrim($path, '/')),
                'locale' => $locale === 'ar' ? 'ar_AR' : 'en_US',
                'site_name' => $siteTitle,
                'image' => $ogImage,
            ],
            'twitter' => [
                'card' => $ogImage ? 'summary_large_image' : 'summary',
                'title' => $title,
                'description' => $description,
                'image' => $ogImage,
            ],
            'robots' => 'index,follow,max-snippet:-1,max-image-preview:large',
        ];

        return array_replace_recursive($data, $overrides);
    }

    /** @param array<string, mixed> $meta */
    public function organizationJsonLd(array $meta): string
    {
        $locale = (string) ($meta['locale'] ?? config('website.default_locale', 'ar'));
        $name = $this->value('organization.name', $locale)
            ?: (string) config('website.name.'.$locale, 'PCDR');
        $shortName = $this->value('organization.short_name', $locale)
            ?: (string) config('website.short_name.'.$locale, 'PCDR');

        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'alternateName' => $shortName,
            'url' => $meta['canonical'],
            'description' => $meta['description'],
            'inLanguage' => [config('website.locales')],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function value(string $key, string $locale): ?string
    {
        $default = match ($key) {
            'organization.name' => config('website.name.'.$locale),
            'organization.short_name' => config('website.short_name.'.$locale, 'PCDR'),
            'seo.site_title' => config('website.short_name.'.$locale, 'PCDR'),
            'seo.default_description' => config('website.description.'.$locale, ''),
            default => null,
        };

        return $this->settings->value($key, $default === null ? null : (string) $default, $locale);
    }
}
