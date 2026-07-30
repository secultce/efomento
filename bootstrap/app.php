<?php

use App\Exceptions\AppException;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(fn (AppException $e) => $e->shouldReport());

        $exceptions->render(function (AppException $e, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['message' => $e->getMessage()]);
            }
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => class_basename($e),
                ], $e->getHttpStatus());
            }
        });

        $exceptions->render(function (InvalidArgumentException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
        Integration::handles($exceptions);
    })->create();
