<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\CmsAccess;
use App\Services\NavigationService;
use App\Services\RbacService;
use App\Services\SettingsService;
use App\Services\SiteSettings;
use App\Support\Locale;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly RbacService $rbac,
        private readonly SettingsService $settings,
    ) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();
        // The locale middleware chain has already resolved the active locale
        // from the route segment, falling back to user and settings defaults.
        $locale = app()->getLocale();
        $direction = Locale::direction($locale);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->toAccessArray(),
                'permissions' => $user ? $this->rbac->permissionsFor($user)->all() : [],
                'roles' => $user ? $this->rbac->rolesFor($user)->all() : [],
                'scopes' => $user ? $this->rbac->publicScopesFor($user) : [],
                'cmsPermissions' => $user ? app(CmsAccess::class)->permissionsFor($user) : [],
            ],
            'locale' => $locale,
            'direction' => $direction,
            'availableLocales' => ['ar', 'en'],
            'translations' => [
                'ui' => __('ui'),
            ],
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
            'phase5' => fn () => __('phase5', [], $locale),
            'publicationCategories' => fn () => __('website.publication.categories', [], $locale),
            'flash' => [
                'success' => fn () => $request->session()->get('success') ?? $request->session()->get('flash.success'),
                'error' => fn () => $request->session()->get('error') ?? $request->session()->get('flash.error'),
                'warning' => fn () => $request->session()->get('flash.warning'),
            ],
            'settings' => [
                'public' => $this->settings->publicSettings($locale),
            ],
        ];
    }
}
