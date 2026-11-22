<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\CmsAccess;
use App\Services\NavigationService;
use App\Services\SiteSettings;
use App\Support\Locale;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $locale = Locale::sanitize($request->route('locale'));
        $user = $request->user();

        return [
            ...parent::share($request),
            'locale' => $locale,
            'direction' => Locale::direction($locale),
            'availableLocales' => config('website.locales', ['ar', 'en']),
            'site' => [
                'name' => config("website.name.{$locale}"),
                'shortName' => config('website.short_name.en'),
                'region' => config("website.region.{$locale}"),
                'slogan' => config("website.slogan.{$locale}"),
                'contact' => app(SiteSettings::class)->contactDetails($locale),
                'social' => app(SiteSettings::class)->socialLinks($locale),
            ],
            'navigation' => app(NavigationService::class)->all($locale),
            'ui' => fn () => __('website.ui', [], $locale),
            'nav' => fn () => __('website.navigation', [], $locale),
            'publicationCategories' => fn () => __('website.publication.categories', [], $locale),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'permissions' => app(CmsAccess::class)->permissionsFor($user),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('flash.success'),
                'error' => fn () => $request->session()->get('flash.error'),
                'warning' => fn () => $request->session()->get('flash.warning'),
            ],
        ];
    }
}
