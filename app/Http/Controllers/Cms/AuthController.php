<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Temporary sign-in shim.
 *
 * Phase 2 owns authentication. This controller exists only so the Phase 3
 * CMS can be exercised in isolation; it is disabled in production unless
 * explicitly enabled, and Phase 2's login should replace it on merge.
 */
final class AuthController extends Controller
{
    public static function localAuthEnabled(): bool
    {
        $configured = config('cms.local_auth_enabled');

        return $configured === null ? ! app()->isProduction() : (bool) $configured;
    }

    public function showLogin(string $locale): Response
    {
        abort_unless(self::localAuthEnabled(), 404);

        return Inertia::render('Cms/Login', [
            'bootstrapHint' => (bool) config('cms.bootstrap.email') && ! app()->isProduction(),
        ]);
    }

    public function login(string $locale, Request $request): RedirectResponse
    {
        abort_unless(self::localAuthEnabled(), 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'cms.login:'.hash('sha256', ($request->ip() ?? '').'|'.$validated['email']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('flash', ['error' => __('website.cms.login_throttled')]);
        }

        if (! Auth::attempt($validated, true)) {
            RateLimiter::hit($key, 600);

            return back()->with('flash', ['error' => __('website.cms.login_failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route('cms.dashboard', ['locale' => $locale]);
    }

    public function logout(string $locale, Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('cms.login', ['locale' => $locale]);
    }
}
