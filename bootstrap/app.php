<?php

use App\Http\Middleware\EnsurePanelRole;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsTeknisi;
use App\Http\Middleware\RecordApiAuditLog;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);

        // Percayai reverse proxy/load balancer agar IP klien asli dipakai untuk
        // rate limiting dan audit log. Batasi lewat TRUSTED_PROXIES.
        // Callback ini berjalan sebelum container config siap, jadi env()
        // dipakai langsung (bukan config()).
        $middleware->trustProxies(at: array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))
        ))));

        $middleware->append(SecurityHeaders::class);

        $middleware->api(append: [
            RecordApiAuditLog::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'teknisi' => EnsureUserIsTeknisi::class,
            'panel' => EnsurePanelRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak terautentikasi. Silakan login terlebih dahulu.',
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dikirim tidak valid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })
    ->create();
