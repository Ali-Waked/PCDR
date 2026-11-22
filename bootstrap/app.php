<?php

use App\Exceptions\InvalidWorkflowTransition;
use App\Exceptions\MediaInUseException;
use App\Http\Middleware\EnsureCmsPermission;
use App\Http\Middleware\HandleInertiaRequests;
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
            SetWebsiteLocale::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'cms' => EnsureCmsPermission::class,
            'locale' => SetWebsiteLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (InvalidWorkflowTransition|MediaInUseException $exception, Request $request) {
            if ($request->expectsJson() || $request->inertia()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return back()->with('flash', ['error' => $exception->getMessage()])->setStatusCode(409);
        });
    })->create();
