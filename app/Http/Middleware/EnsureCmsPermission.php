<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
            if (Route::has('cms.login')) {
                return redirect()->route('cms.login', ['locale' => $request->route('locale')]);
            }

            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (! app(CmsAccess::class)->allows(Auth::user(), $permission)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this CMS area.');
        }

        return $next($request);
    }
}
