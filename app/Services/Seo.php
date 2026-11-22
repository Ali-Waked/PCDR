<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Support\Locale;

final class Seo
{
    /** @param array<string, mixed> $overrides */
    public function for(
        string $locale,
        string $title,
        ?string $description = null,
        ?string $path = null,
        ?Media $image = null,
        array $overrides = [],
    ): array {
        $description ??= (string) config('website.description.'.$locale, '');
        $path ??= request()->path();
        $alternates = Locale::alternates($path);

        $data = [
            'title' => $title.' — '.(string) config('website.short_name.'.$locale, 'PCDR'),
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
                'site_name' => (string) config('website.short_name.'.$locale, 'PCDR'),
                'image' => $image?->url(),
            ],
            'twitter' => [
                'card' => $image ? 'summary_large_image' : 'summary',
                'title' => $title,
                'description' => $description,
                'image' => $image?->url(),
            ],
            'robots' => 'index,follow,max-snippet:-1,max-image-preview:large',
        ];

        return array_replace_recursive($data, $overrides);
    }

    /** @param array<string, mixed> $meta */
    public function organizationJsonLd(array $meta): string
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => (string) config('website.name.'.$meta['locale'], 'PCDR'),
            'alternateName' => 'PCDR',
            'url' => $meta['canonical'],
            'description' => $meta['description'],
            'inLanguage' => [config('website.locales')],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
