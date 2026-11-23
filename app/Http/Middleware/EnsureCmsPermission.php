<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Cms\AuthController;
use App\Services\CmsAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCmsPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            $locale = $request->route('locale');

            // The temporary CMS shim is disabled in production. Sending guests
            // there would produce a 404 instead of the real Phase 2 login.
            if (Route::has('login.show') && ! AuthController::localAuthEnabled()) {
                return redirect()->route('login.show', ['locale' => $locale]);
            }

            if (Route::has('cms.login')) {
                return redirect()->route('cms.login', ['locale' => $locale]);
            }

            if (Route::has('login.show')) {
                return redirect()->route('login.show', ['locale' => $locale]);
            }

            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (! app(CmsAccess::class)->allows(Auth::user(), $permission)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this CMS area.');
        }

        return $next($request);
    }
}
