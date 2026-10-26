<?php

use App\Http\Middleware\EnsurePanelRole;
use App\Http\Middleware\SecurityHeaders;
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
        // Header keamanan global + deteksi HTTPS di belakang reverse proxy/ngrok.
        $middleware->append(SecurityHeaders::class);

        // Proxy tepercaya: ngrok lokal meneruskan request dari loopback.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // Gerbang panel admin/teknisi berbasis cookie HttpOnly.
        $middleware->alias([
            'panel' => EnsurePanelRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
