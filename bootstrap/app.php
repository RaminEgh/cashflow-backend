<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsOrgan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies only in production (for HTTPS detection behind reverse proxy)
        // On localhost, don't trust proxies to avoid redirect issues
        // if (app()->environment('production')) {
        //     $middleware->trustProxies(at: '*');
        // }

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
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('horizon*') || $request->is('horizon/*')) {
                return redirect()->route('horizon.login.show');
            }

            return null;
        });
    })->create();
