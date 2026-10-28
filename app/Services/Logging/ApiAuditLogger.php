<?php

namespace App\Services\Logging;

use App\Models\ApiAuditLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Penulis tunggal tabel api_audit_logs.
 *
 * Kegagalan menulis log tidak boleh menggagalkan request utama, sehingga semua
 * error ditelan dan hanya dicatat ke application log.
 */
class ApiAuditLogger
{
    public function __construct(private SensitiveDataSanitizer $sanitizer) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function record(array $attributes): ?ApiAuditLog
    {
        try {
            $attributes['request_summary'] = $this->sanitizer->sanitizeArray(
                $attributes['request_summary'] ?? null
            );
            $attributes['response_summary'] = $this->sanitizer->sanitizeArray(
                $attributes['response_summary'] ?? null
            );

            if (isset($attributes['error_message'])) {
                $attributes['error_message'] = mb_substr(
                    $this->sanitizer->scrubText((string) $attributes['error_message']),
                    0,
                    2000
                );
            }

            return ApiAuditLog::create($attributes);
        } catch (Throwable $e) {
            Log::warning('Gagal menulis api_audit_logs.', [
                'error' => $e::class,
            ]);

            return null;
        }
    }
}
