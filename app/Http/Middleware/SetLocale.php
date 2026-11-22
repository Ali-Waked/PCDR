<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function __construct(private readonly SettingsService $settings) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $available = $this->settings->get('localization.available_locales') ?: ['ar', 'en'];
        $available = is_array($available) ? array_values(array_intersect($available, ['ar', 'en'])) : ['ar', 'en'];
        if ($available === []) {
            $available = ['ar', 'en'];
        }

        $segment = $request->route('locale');

        if (is_string($segment) && in_array($segment, $available, true)) {
            $locale = $segment;
        } elseif (($user = $request->user()) !== null && in_array($user->locale, $available, true)) {
            $locale = $user->locale;
        } else {
            $configured = $this->settings->get('localization.default_locale');
            $locale = in_array($configured, $available, true) ? $configured : 'ar';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
