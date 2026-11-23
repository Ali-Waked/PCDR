<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Fallback handler for unmatched requests.
 *
 * It redirects a locale-less URL to an existing localized GET route when one
 * exists. It never changes a known route and never redirects methods other than
 * GET/HEAD. Requests that cannot be mapped safely render the normal 404.
 */
final class RedirectToLocalizedUrl
{
    public function __construct(private readonly LocaleResolver $locales) {}

    public function __invoke(Request $request): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $this->notFound($request);
        }

        $path = $this->safePath($request);

        if ($path === null) {
            return $this->notFound($request);
        }

        $segments = array_values(array_filter(
            explode('/', $path),
            static fn (string $segment): bool => $segment !== '',
        ));

        if ($segments === []) {
            return $this->notFound($request);
        }

        $firstSegment = $segments[0];
        $normalizedSegment = $this->locales->normalize($firstSegment);

        if ($normalizedSegment !== null && $normalizedSegment !== $firstSegment) {
            $segments[0] = $normalizedSegment;
            $lowercasePath = '/'.implode('/', $segments);

            return $this->routeExists($lowercasePath)
                ? $this->redirect($lowercasePath, $request)
                : $this->notFound($request);
        }

        if ($normalizedSegment !== null || $this->isReserved($segments)) {
            return $this->notFound($request);
        }

        foreach ($this->locales->candidates($request) as $locale) {
            $candidate = '/'.$locale.'/'.implode('/', $segments);

            if ($this->routeExists($candidate)) {
                return $this->redirect($candidate, $request);
            }
        }

        return $this->notFound($request);
    }

    /**
     * Returns a sanitized path, or null when the request must be rejected.
     * The raw request target is checked before Symfony's decoded path info is
     * used so encoded control characters and backslashes cannot be smuggled.
     */
    private function safePath(Request $request): ?string
    {
        $raw = (string) $request->getRequestUri();

        if (strlen($raw) > (int) config('locales.max_path_length', 2048)) {
            return null;
        }

        if (preg_match('/%[0-9a-f]{2}/i', $raw) === 1
            && preg_match('/[\x00-\x1F\x7F]/', rawurldecode($raw)) === 1) {
            return null;
        }

        $path = $request->getPathInfo();

        if ($path === '' || $path[0] !== '/') {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $path) === 1) {
            return null;
        }

        if (str_contains($path, '//')) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', ltrim($path, '/')) === 1) {
            return null;
        }

        return $path;
    }

    /** @param list<string> $segments */
    private function isReserved(array $segments): bool
    {
        $first = strtolower($segments[0]);
        $reserved = array_map('strtolower', (array) config('locales.reserved_prefixes', []));

        if (in_array($first, $reserved, true)) {
            return true;
        }

        foreach ((array) config('locales.reserved_patterns', []) as $pattern) {
            if (is_string($pattern) && preg_match($pattern, $first) === 1) {
                return true;
            }
        }

        $last = strtolower((string) end($segments));

        return preg_match('/\.[a-z0-9]{1,10}$/', $last) === 1;
    }

    private function routeExists(string $path): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));

            // The fallback itself matches every path; it is not a localized page.
            return ! (bool) ($route->isFallback ?? false);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
    }

    private function redirect(string $path, Request $request): RedirectResponse
    {
        $query = $request->getQueryString();

        if (is_string($query) && $query !== '') {
            $path .= '?'.$query;
        }

        return new RedirectResponse($path, Response::HTTP_FOUND, [
            'Vary' => 'Accept-Language, Cookie',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function notFound(Request $request): never
    {
        app()->setLocale($this->locales->resolve($request));

        abort(Response::HTTP_NOT_FOUND);
    }
}
