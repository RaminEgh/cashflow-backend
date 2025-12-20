<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsOrgan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all proxies (for HTTPS detection behind reverse proxy)
        $middleware->trustProxies(at: '*');

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->group('is-admin', [
            \App\Http\Middleware\IsAdmin::class,
        ]);

        $middleware->group('is-organ', [
            \App\Http\Middleware\IsOrgan::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'is-admin' => IsAdmin::class,
            'is-organ' => IsOrgan::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirect unauthenticated users to Horizon login when accessing Horizon
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('horizon*') || $request->is('horizon/*')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
                return redirect()->route('horizon.login.show');
            }
        });
    })->create();
