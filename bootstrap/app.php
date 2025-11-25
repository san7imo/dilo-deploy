<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * --------------------------------------------------------
         *  MIDDLEWARES GLOBALES
         * --------------------------------------------------------
         * Aquí se agregan los middlewares que deben aplicarse
         * en todas las rutas del stack "web" (Inertia, assets, etc.).
         */
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        /**
         * --------------------------------------------------------
         *  ALIAS DE MIDDLEWARES (equivalente al antiguo Kernel)
         * --------------------------------------------------------
         * Estos alias pueden usarse directamente en las rutas.
         */
        $middleware->alias([
            // Autenticación base de Laravel / Jetstream
            'auth'     => \Illuminate\Auth\Middleware\Authenticate::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // Middlewares del paquete Spatie Laravel Permission
            // ⚠️ IMPORTANTE: el namespace correcto es singular "Middleware"
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
