<?php

namespace App\Services\Logging;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Penulis tunggal tabel notification_logs.
 *
 * Status hanya dinaikkan berdasarkan bukti yang tersedia:
 * - queued  : pesan sudah diserahkan ke queue Laravel,
 * - sent    : transport mail menerima pesan (event MessageSent),
 * - failed  : pengiriman melempar error,
 * - delivered/bounced hanya boleh diisi bila provider memberi callback nyata.
 *
 * "sent" tidak berarti email sudah masuk kotak masuk penerima.
 */
class NotificationLogger
{
    public function __construct(private SensitiveDataSanitizer $sanitizer) {}

    public function queued(
        string $notificationType,
        ?string $userId,
        ?string $recipient,
        ?string $referenceType = null,
        ?string $referenceId = null,
        string $channel = 'email',
        ?string $provider = null
    ): ?NotificationLog {
        try {
            return NotificationLog::create([
                'id_pengguna' => $userId,
                'channel' => mb_substr($channel, 0, 20),
                'provider' => mb_substr($provider ?? $this->providerName(), 0, 50),
                'notification_type' => mb_substr($notificationType, 0, 100),
                'recipient_masked' => $this->sanitizer->maskRecipient($recipient),
                'reference_type' => $referenceType !== null
                    ? mb_substr($referenceType, 0, 50)
                    : null,
                'reference_id' => $referenceId !== null
                    ? mb_substr($referenceId, 0, 100)
                    : null,
                'provider_message_id' => null,
                'delivery_status' => 'queued',
                'attempt_count' => 0,
                'error_code' => null,
                'error_message' => null,
                'sent_at' => null,
                'delivered_at' => null,
            ]);
        } catch (Throwable $e) {
            $this->logFailure($e);

            return null;
        }
    }

    public function markAttempt(?int $id): void
    {
        $this->update($id, function (NotificationLog $log) {
            $log->attempt_count = (int) $log->attempt_count + 1;
        });
    }

    public function markSent(?int $id, ?string $providerMessageId = null): void
    {
        $this->update($id, function (NotificationLog $log) use ($providerMessageId) {
            $log->delivery_status = 'sent';
            $log->sent_at = $log->sent_at ?? now();
            $log->error_code = null;
            $log->error_message = null;

            if ($providerMessageId !== null && $providerMessageId !== '') {
                $log->provider_message_id = mb_substr($providerMessageId, 0, 150);
            }
        });
    }

    public function markFailed(
        ?int $id,
        Throwable|string $error,
        ?string $errorCode = null
    ): void {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;
        $code = $errorCode ?? ($error instanceof Throwable ? class_basename($error) : null);

        $this->update($id, function (NotificationLog $log) use ($message, $code) {
            $log->delivery_status = 'failed';
            $log->attempt_count = (int) $log->attempt_count + 1;
            $log->error_code = $code !== null ? mb_substr($code, 0, 100) : null;
            $log->error_message = mb_substr(
                $this->sanitizer->scrubText($message),
                0,
                2000
            );
        });
    }

    /**
     * Dipakai hanya bila provider benar-benar mengirim callback delivery.
     */
    public function markDelivered(?int $id): void
    {
        $this->update($id, function (NotificationLog $log) {
            $log->delivery_status = 'delivered';
            $log->delivered_at = $log->delivered_at ?? now();
        });
    }

    /**
     * Dipakai hanya bila provider benar-benar mengirim callback bounce.
     */
    public function markBounced(?int $id, ?string $reason = null): void
    {
        $this->update($id, function (NotificationLog $log) use ($reason) {
            $log->delivery_status = 'bounced';
            $log->error_message = $reason !== null
                ? mb_substr($this->sanitizer->scrubText($reason), 0, 2000)
                : $log->error_message;
        });
    }

    public function providerName(): string
    {
        $mailer = (string) config('mail.default', 'smtp');

        if ($mailer !== 'smtp') {
            return mb_substr($mailer, 0, 50);
        }

        $host = mb_strtolower((string) config('mail.mailers.smtp.host', ''));

        return match (true) {
            str_contains($host, 'mailjet') => 'mailjet',
            str_contains($host, 'mailgun') => 'mailgun',
            str_contains($host, 'sendgrid') => 'sendgrid',
            str_contains($host, 'postmark') => 'postmark',
            default => 'smtp',
        };
    }

    private function update(?int $id, callable $callback): void
    {
        if ($id === null) {
            return;
        }

        try {
            $log = NotificationLog::find($id);

            if ($log === null) {
                return;
            }

            $callback($log);
            $log->save();
        } catch (Throwable $e) {
            $this->logFailure($e);
        }
    }

    private function logFailure(Throwable $e): void
    {
        try {
            Log::warning('Gagal menulis notification_logs.', ['error' => $e::class]);
        } catch (Throwable) {
            // Abaikan: logging tidak boleh memutus pengiriman email.
        }
    }
}
