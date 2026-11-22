<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetWebsiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if ($locale !== null && ! Locale::isValid((string) $locale)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $locale = Locale::sanitize(is_string($locale) ? $locale : null);
        app()->setLocale($locale);
        app()->setFallbackLocale((string) config('website.fallback_locale', 'en'));
        Carbon::setLocale($locale);

        return $next($request);
    }
}
