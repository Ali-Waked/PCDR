<?php

namespace App\Http\Middleware;

use App\Services\RbacService;
use App\Services\SettingsService;
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
        $locale = app()->getLocale();
        $direction = $locale === 'ar' ? 'rtl' : 'ltr';

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user?->toAccessArray(),
                'permissions' => $user ? $this->rbac->permissionsFor($user)->all() : [],
                'roles' => $user ? $this->rbac->rolesFor($user)->all() : [],
                'scopes' => $user ? $this->rbac->publicScopesFor($user) : [],
            ],
            'locale' => $locale,
            'direction' => $direction,
            'availableLocales' => ['ar', 'en'],
            'translations' => [
                'ui' => __('ui'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'settings' => [
                'public' => $this->settings->publicSettings($locale),
            ],
        ];
    }
}
