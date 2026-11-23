<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RbacService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks direct-URL access to operational modules that are preserved but
 * hidden from the delivered website CMS. Existing permission middleware
 * remains the second authorization boundary.
 */
final class EnsureDashboardFeatureAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = (string) preg_replace('/^(ar|en)\//', '', $request->path());
        $feature = $this->hiddenFeatureFor($path);

        if ($feature === null) {
            return $next($request);
        }

        if (config('dashboard_features.hidden_modules_access', false)) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        // The Platform Owner remains the technical custodian of preserved
        // modules. Ordinary CMS users need an explicitly granted legacy
        // operational permission (used only for restoration / regression).
        if ($user->isPlatformOwner()) {
            return $next($request);
        }

        $rbac = app(RbacService::class);
        $legacyModules = (array) ($feature['legacy_modules'] ?? []);

        if ($legacyModules !== []) {
            foreach ((array) config('permissions', []) as $permission => $definition) {
                $module = is_array($definition) ? ($definition['module'] ?? null) : null;

                if (in_array($module, $legacyModules, true) && $rbac->has($user, (string) $permission)) {
                    return $next($request);
                }
            }
        }

        foreach ($feature['legacy_permissions'] ?? [] as $permission) {
            if ($rbac->has($user, (string) $permission)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN, 'This module is not part of the website CMS scope.');
    }

    /** @return array{state: string, legacy_permissions: array<int, string>}|null */
    private function hiddenFeatureFor(string $path): ?array
    {
        /** @var array<string, array{state: string, path_prefix?: string, legacy_permissions?: array<int, string>}> $features */
        $features = config('dashboard_features.features', []);

        $matches = [];

        foreach ($features as $key => $feature) {
            if (($feature['state'] ?? null) !== 'hidden') {
                continue;
            }

            $prefix = $feature['path_prefix'] ?? $key;

            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                $matches[$prefix] = $feature;
            }
        }

        if ($matches === []) {
            return null;
        }

        // Prefer the longest prefix so Phase 5 is classified before Phase 4.
        krsort($matches);

        return reset($matches) ?: null;
    }
}
