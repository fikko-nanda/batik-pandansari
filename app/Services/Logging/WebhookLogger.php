<?php

namespace App\Services\Logging;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Penulis tunggal tabel webhook_logs.
 *
 * Log dicatat sejak webhook diterima (termasuk signature tidak valid) dan
 * diperbarui saat pemrosesan selesai/gagal. Kegagalan menulis log tidak boleh
 * menghentikan validasi signature maupun pemrosesan pembayaran.
 */
class WebhookLogger
{
    public function __construct(private SensitiveDataSanitizer $sanitizer) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function received(
        Request $request,
        array $payload,
        bool $signatureValid,
        string $provider = 'midtrans'
    ): ?WebhookLog {
        try {
            $summary = $this->sanitizer->sanitizeArray($payload) ?? [];
            $eventId = $this->stringOrNull($payload['event_id'] ?? null);
            $eventType = $this->stringOrNull(
                $payload['event_type'] ?? $payload['transaction_status'] ?? null
            );
            $transactionId = $this->stringOrNull($payload['transaction_id'] ?? null);

            return WebhookLog::create([
                'id_webhook_log' => (string) Str::uuid(),
                'provider' => mb_substr($provider, 0, 50),
                'event_id' => $eventId !== null ? mb_substr($eventId, 0, 100) : null,
                'event_type' => $eventType !== null ? mb_substr($eventType, 0, 100) : null,
                'provider_transaction_id' => $transactionId !== null
                    ? mb_substr($transactionId, 0, 150)
                    : null,
                'idempotency_key' => $this->idempotencyKey($provider, $payload),
                'id_order' => null,
                'endpoint' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
                'method' => $request->method(),
                // Kolom lama tetap diisi ringkasan yang sudah disanitasi;
                // signature asli tidak pernah disimpan.
                'payload' => json_encode($summary) ?: '{}',
                'signature' => null,
                'signature_valid' => $signatureValid,
                'status' => $signatureValid ? 'diterima' : 'ditolak',
                'processing_status' => $signatureValid ? 'received' : 'rejected',
                'payload_summary' => $summary,
                'response_summary' => null,
                'attempt_count' => 1,
                'error_message' => $signatureValid ? null : 'Signature notifikasi tidak valid.',
                'received_at' => now(),
                'processed_at' => $signatureValid ? null : now(),
                'http_status' => null,
                'alamat_ip' => mb_substr((string) $request->ip(), 0, 45),
                'catatan' => $signatureValid
                    ? 'Webhook diterima, menunggu pemrosesan.'
                    : 'Webhook ditolak karena signature tidak valid.',
            ]);
        } catch (Throwable $e) {
            $this->logFailure($e);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $response
     */
    public function markProcessed(
        ?WebhookLog $log,
        array $result,
        int $httpStatus,
        string $note,
        array $response = [],
        ?string $idOrder = null,
        ?string $transactionId = null
    ): void {
        $duplicate = (bool) ($result['duplicate'] ?? false);

        $this->update($log, [
            'id_order' => $idOrder,
            'provider_transaction_id' => $transactionId !== null
                ? mb_substr($transactionId, 0, 150)
                : $log?->provider_transaction_id,
            'status' => $duplicate ? 'ganda' : 'diproses',
            'processing_status' => $duplicate ? 'duplicate' : 'processed',
            'http_status' => $httpStatus,
            'response_summary' => $this->sanitizer->sanitizeArray($response),
            'error_message' => null,
            'processed_at' => now(),
            'catatan' => mb_substr($note, 0, 2000),
        ]);
    }

    public function markRejected(
        ?WebhookLog $log,
        int $httpStatus,
        string $note,
        array $response = []
    ): void {
        $this->update($log, [
            'status' => 'ditolak',
            'processing_status' => 'rejected',
            'signature_valid' => false,
            'http_status' => $httpStatus,
            'response_summary' => $this->sanitizer->sanitizeArray($response),
            'error_message' => $note,
            'processed_at' => now(),
            'catatan' => mb_substr($note, 0, 2000),
        ]);
    }

    public function markFailed(
        ?WebhookLog $log,
        int $httpStatus,
        string $errorMessage,
        string $note,
        array $response = [],
        ?string $idOrder = null
    ): void {
        $this->update($log, [
            'id_order' => $idOrder,
            'status' => 'gagal',
            'processing_status' => 'failed',
            'http_status' => $httpStatus,
            'response_summary' => $this->sanitizer->sanitizeArray($response),
            'error_message' => mb_substr(
                $this->sanitizer->scrubText($errorMessage),
                0,
                2000
            ),
            'processed_at' => now(),
            'catatan' => mb_substr($note, 0, 2000),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function idempotencyKey(string $provider, array $payload): string
    {
        $reference = $payload['event_id']
            ?? $payload['transaction_id']
            ?? $payload['order_id']
            ?? '';

        return hash('sha256', implode('|', [
            $provider,
            (string) $reference,
            (string) ($payload['event_type'] ?? $payload['transaction_status'] ?? ''),
            (string) ($payload['gross_amount'] ?? ''),
        ]));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function update(?WebhookLog $log, array $fields): void
    {
        if ($log === null) {
            return;
        }

        try {
            $log->update($fields);
        } catch (Throwable $e) {
            $this->logFailure($e);
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function logFailure(Throwable $e): void
    {
        try {
            Log::warning('Gagal menulis webhook_logs.', ['error' => $e::class]);
        } catch (Throwable) {
            // Abaikan: logging tidak boleh memutus alur pembayaran.
        }
    }
}
