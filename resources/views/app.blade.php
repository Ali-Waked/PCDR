<!DOCTYPE html>
@php
    $locale = $page['props']['locale'] ?? app()->getLocale();
    $direction = $page['props']['direction'] ?? ($locale === 'ar' ? 'rtl' : 'ltr');
    $siteName = $page['props']['settings']['public']['general.site_name'] ?? config('app.name', 'PCDR');
@endphp
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title inertia>{{ $siteName }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-surface text-ink antialiased">
        @inertia
        <x-devtools />
    </body>
</html>
