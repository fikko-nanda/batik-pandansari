<?php

namespace App\Http\Middleware;

use App\Services\Logging\ApiAuditLogger;
use App\Services\Logging\SensitiveDataSanitizer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mencatat request API yang relevan saja:
 * - seluruh respons error (status >= 400),
 * - endpoint autentikasi/OTP,
 * - integrasi eksternal (Midtrans/pembayaran), dan
 * - checkout.
 *
 * Request baca katalog/CMS biasa sengaja tidak dicatat agar tabel audit tidak
 * dipenuhi trafik yang tidak dibutuhkan untuk troubleshooting.
 */
class RecordApiAuditLog
{
    private const EXCLUDED_PREFIXES = [
        'api/teknisi',
        'api/test-json',
    ];

    private const RELEVANT_PREFIXES = [
        'api/auth',
        'api/login',
        'api/register',
        'api/me/google',
        'api/otp',
        'api/forgot-password',
        'api/midtrans',
        'api/payments',
        'api/checkout',
    ];

    public function __construct(
        private ApiAuditLogger $logger,
        private SensitiveDataSanitizer $sanitizer
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $correlationId = $this->resolveCorrelationId($request);
        $request->attributes->set('correlation_id', $correlationId);

        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->write($request, null, $correlationId, $startedAt, $e);

            throw $e;
        }

        $response->headers->set('X-Correlation-ID', $correlationId);

        if ($this->isRelevant($request, $response->getStatusCode())) {
            $this->write($request, $response, $correlationId, $startedAt);
        }

        return $response;
    }

    private function resolveCorrelationId(Request $request): string
    {
        $incoming = trim((string) $request->header('X-Correlation-ID', ''));

        if ($incoming !== '' && mb_strlen($incoming) <= 100) {
            return $incoming;
        }

        return (string) Str::uuid();
    }

    private function isRelevant(Request $request, int $status): bool
    {
        $path = ltrim($request->path(), '/');

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        if ($status >= 400) {
            return true;
        }

        foreach (self::RELEVANT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return str_starts_with($path, 'api/orders/')
            && str_contains($path, '/payment');
    }

    private function write(
        Request $request,
        ?Response $response,
        string $correlationId,
        float $startedAt,
        ?Throwable $exception = null
    ): void {
        $status = $response?->getStatusCode() ?? 500;
        $responseSummary = $response !== null
            ? $this->sanitizer->summarizeResponse($response)
            : null;

        $errorCode = null;
        $errorMessage = null;

        if ($exception !== null) {
            $errorCode = class_basename($exception);
            $errorMessage = $exception->getMessage();
        } elseif ($status >= 400) {
            $errorCode = $this->errorCodeFor($status);
            $errorMessage = is_array($responseSummary)
                ? ($responseSummary['message'] ?? null)
                : null;
        }

        $this->logger->record([
            'correlation_id' => $correlationId,
            'id_pengguna' => optional($request->user())->getAuthIdentifier(),
            'service_name' => $this->resolveServiceName($request),
            'http_method' => strtoupper($request->method()),
            'endpoint' => mb_substr('/'.ltrim($request->path(), '/'), 0, 500),
            'http_status' => $status,
            'request_summary' => $this->sanitizer->summarizeRequest($request),
            'response_summary' => $responseSummary,
            'latency_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'ip_address' => mb_substr((string) $request->ip(), 0, 45),
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    private function resolveServiceName(Request $request): string
    {
        $segments = explode('/', trim($request->path(), '/'));
        $root = $segments[1] ?? 'api';

        return match ($root) {
            'auth', 'login', 'register', 'me', 'logout', 'otp', 'forgot-password' => 'auth',
            'payments', 'midtrans' => 'payment',
            'orders', 'checkout' => 'order',
            'admin' => 'admin',
            'products', 'categories', 'cms', 'gambar-ukuran' => 'catalog',
            default => mb_substr($root !== '' ? $root : 'api', 0, 50),
        };
    }

    private function errorCodeFor(int $status): string
    {
        return match ($status) {
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            419 => 'CSRF_TOKEN_MISMATCH',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMITED',
            default => $status >= 500 ? 'SERVER_ERROR' : 'HTTP_'.$status,
        };
    }
}
