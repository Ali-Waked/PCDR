<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Services\RbacService;
use App\Services\SessionInvalidationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountActive
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RbacService $rbac,
        private readonly SessionInvalidationService $sessions,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->isActive()) {
            return $next($request);
        }

        $this->audit->record('auth.blocked_inactive', 'users', (string) $user->id);
        $this->sessions->invalidate($user);
        $this->rbac->forgetUser($user);
        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.account_inactive')], 403);
        }

        return redirect()->route('login.show', ['locale' => $request->route('locale') ?? app()->getLocale()])
            ->withErrors(['email' => __('auth.account_inactive')]);
    }
}
