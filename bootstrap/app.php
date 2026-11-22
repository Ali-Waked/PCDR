<?php

use App\Exceptions\InvalidDomainTransition;
use App\Exceptions\InvalidWorkflowTransition;
use App\Exceptions\MediaInUseException;
use App\Exceptions\Phase5GovernanceException;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureCmsPermission;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetWebsiteLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Settings/user-aware locale first; the route segment wins when present.
            SetLocale::class,
            SetWebsiteLocale::class,
            HandleInertiaRequests::class,
            AddSecurityHeaders::class,
        ]);

        $middleware->alias([
            'account.active' => EnsureAccountActive::class,
            'permission' => RequirePermission::class,
            'cms' => EnsureCmsPermission::class,
            'locale' => SetWebsiteLocale::class,
        ]);

        // Inbound webhooks authenticate with a timestamped HMAC signature and
        // nonce replay protection rather than a browser CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/inbound/*']);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login.show', [
            'locale' => $request->route('locale') ?? 'ar',
        ]));

        $middleware->redirectUsersTo(fn (Request $request) => route('dashboard', [
            'locale' => $request->route('locale') ?? app()->getLocale() ?? 'ar',
        ]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (InvalidWorkflowTransition|InvalidDomainTransition|MediaInUseException|Phase5GovernanceException $exception, Request $request) {
            if ($request->expectsJson() || $request->inertia()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return back()->with('flash', ['error' => $exception->getMessage()])->setStatusCode(409);
        });
    })->create();
