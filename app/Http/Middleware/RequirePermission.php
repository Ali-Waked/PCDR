<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RbacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePermission
{
    public function __construct(private readonly RbacService $rbac) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user !== null && $this->rbac->has($user, $permission)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json(['message' => __('auth.forbidden')], 403);
        }

        abort(403, __('auth.forbidden'));
    }
}
