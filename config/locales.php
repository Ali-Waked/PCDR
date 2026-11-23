<?php

declare(strict_types=1);

/*
 * Locale routing configuration used by the unprefixed-URL fallback.
 *
 * These values are intentionally kept separate from content configuration so
 * routing safety decisions do not depend on database-backed settings.
 */
return [
    'supported' => ['ar', 'en'],

    'default' => 'ar',

    /*
     * Prefixes owned by the framework, assets, or system endpoints. Requests
     * beginning with one of these segments are never locale-redirected.
     */
    'reserved_prefixes' => [
        '_ignition',
        'api',
        'assets',
        'broadcasting',
        'build',
        'favicon.ico',
        'horizon',
        'livewire',
        'robots.txt',
        'sanctum',
        'site.webmanifest',
        'storage',
        'telescope',
        'up',
        'vendor',
    ],

    /** Additional reserved-path patterns, matched against the first segment. */
    'reserved_patterns' => [
        '#^sitemap[^/]*\.xml$#i',
    ],

    /** Cookie names checked, in order, when resolving a visitor's locale. */
    'cookie_names' => ['pcdr_locale', 'locale'],

    /** Session keys checked, in order, when resolving a visitor's locale. */
    'session_keys' => ['locale', 'pcdr_locale'],

    'max_path_length' => 2048,
];
