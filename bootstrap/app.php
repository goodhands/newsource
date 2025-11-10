<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // set api routes as the base routes
            Route::middleware('api')
                ->prefix('')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\AcceptApiKeyFromQuery::class,
        ]);

        $middleware->alias([
            'auth.api' => \App\Http\Middleware\AuthenticateApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || !$request->is('web/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthenticated. api-key or Authorization header required.',
                ], 401);
            }
        });
    })->create();
