<?php

namespace App\Http\Controllers\Api\Teknisi;

use App\Http\Controllers\Controller;
use App\Models\ApiAuditLog;
use App\Models\NotificationLog;
use App\Models\WebhookLog;
use App\Services\Logging\SensitiveDataSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Endpoint monitoring/troubleshooting untuk role teknisi.
 *
 * Semua data berasal dari tabel nyata (api_audit_logs, webhook_logs,
 * notification_logs, jobs, failed_jobs) dan tidak pernah menampilkan kredensial
 * atau payload mentah yang sensitif.
 */
class TeknisiController extends Controller
{
    public function __construct(private SensitiveDataSanitizer $sanitizer) {}

    public function dashboard(): JsonResponse
    {
        $since = now()->subDay();

        $auditTotal = ApiAuditLog::where('created_at', '>=', $since)->count();
        $auditErrors = ApiAuditLog::where('created_at', '>=', $since)
            ->where('http_status', '>=', 400)
            ->count();

        $topServices = ApiAuditLog::query()
            ->select('service_name')
            ->selectRaw('count(*) as total')
            ->where('created_at', '>=', $since)
            ->groupBy('service_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'service_name' => $row->service_name,
                'total' => (int) $row->total,
            ])
            ->all();

        $webhookCounts = WebhookLog::query()
            ->select('processing_status')
            ->selectRaw('count(*) as total')
            ->where('received_at', '>=', $since)
            ->groupBy('processing_status')
            ->pluck('total', 'processing_status');

        $notificationCounts = NotificationLog::query()
            ->select('delivery_status')
            ->selectRaw('count(*) as total')
            ->where('created_at', '>=', $since)
            ->groupBy('delivery_status')
            ->pluck('total', 'delivery_status');

        $queueConnection = (string) config('queue.default');

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard teknisi berhasil diambil.',
            'data' => [
                'generated_at' => now()->toIso8601String(),
                'window' => '24 jam terakhir',
                'audit' => [
                    'total_24h' => $auditTotal,
                    'errors_24h' => $auditErrors,
                    'error_rate_24h' => $auditTotal > 0
                        ? round(($auditErrors / $auditTotal) * 100, 2)
                        : 0,
                    'top_services_24h' => $topServices,
                ],
                'webhooks' => [
                    'received_24h' => (int) $webhookCounts->sum(),
                    'processed_24h' => (int) ($webhookCounts['processed'] ?? 0),
                    'duplicate_24h' => (int) ($webhookCounts['duplicate'] ?? 0),
                    'failed_24h' => (int) ($webhookCounts['failed'] ?? 0),
                    'rejected_24h' => (int) ($webhookCounts['rejected'] ?? 0),
                ],
                'notifications' => [
                    'queued_24h' => (int) ($notificationCounts['queued'] ?? 0),
                    'sent_24h' => (int) ($notificationCounts['sent'] ?? 0),
                    'failed_24h' => (int) ($notificationCounts['failed'] ?? 0),
                    'bounced_24h' => (int) ($notificationCounts['bounced'] ?? 0),
                    'delivered_24h' => (int) ($notificationCounts['delivered'] ?? 0),
                ],
                'queue' => $this->queueSummary($queueConnection),
                'system' => [
                    'environment' => app()->environment(),
                    'laravel_version' => app()->version(),
                    'php_version' => PHP_VERSION,
                ],
            ],
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
            'http_status' => ['nullable', 'integer', 'min:100', 'max:599'],
            'service_name' => ['nullable', 'string', 'max:50'],
            'endpoint' => ['nullable', 'string', 'max:500'],
            'correlation_id' => ['nullable', 'string', 'max:100'],
            'error_code' => ['nullable', 'string', 'max:100'],
            'id_pengguna' => ['nullable', 'string', 'max:36'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ApiAuditLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id_audit_log');

        $this->applyDateRange($query, $validated, 'created_at');

        if (! empty($validated['http_status'])) {
            $query->where('http_status', (int) $validated['http_status']);
        }

        if (! empty($validated['status'])) {
            $status = mb_strtolower($validated['status']);

            if ($status === 'error' || $status === 'gagal') {
                $query->where('http_status', '>=', 400);
            } elseif ($status === 'success' || $status === 'sukses') {
                $query->where(function (Builder $inner) {
                    $inner->where('http_status', '<', 400)->orWhereNull('http_status');
                });
            } elseif (ctype_digit($status)) {
                $query->where('http_status', (int) $status);
            }
        }

        if (! empty($validated['service_name'])) {
            $query->where('service_name', $validated['service_name']);
        }

        if (! empty($validated['endpoint'])) {
            $query->where('endpoint', 'like', '%'.$validated['endpoint'].'%');
        }

        if (! empty($validated['correlation_id'])) {
            $query->where('correlation_id', $validated['correlation_id']);
        }

        if (! empty($validated['error_code'])) {
            $query->where('error_code', $validated['error_code']);
        }

        if (! empty($validated['id_pengguna'])) {
            $query->where('id_pengguna', $validated['id_pengguna']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('correlation_id', 'like', '%'.$search.'%')
                    ->orWhere('endpoint', 'like', '%'.$search.'%')
                    ->orWhere('error_message', 'like', '%'.$search.'%')
                    ->orWhere('error_code', 'like', '%'.$search.'%');
            });
        }

        $logs = $query->paginate($this->perPage($validated))->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Daftar audit log berhasil diambil.',
            'data' => $logs,
        ]);
    }

    public function auditLogDetail(string $id): JsonResponse
    {
        $log = ApiAuditLog::find($id);

        if (! $log) {
            return $this->notFound('Audit log tidak ditemukan.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail audit log berhasil diambil.',
            'data' => $log,
        ]);
    }

    public function webhookLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'provider' => ['nullable', 'string', 'max:50'],
            'processing_status' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:30'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'signature_valid' => ['nullable', 'boolean'],
            'id_transaksi' => ['nullable', 'string', 'max:150'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = WebhookLog::query()
            ->orderByDesc('received_at')
            ->orderByDesc('dibuat_pada');

        $this->applyDateRange($query, $validated, 'received_at');

        if (! empty($validated['provider'])) {
            $query->where('provider', $validated['provider']);
        }

        $processingStatus = $validated['processing_status'] ?? $validated['status'] ?? null;

        if (! empty($processingStatus)) {
            $query->where('processing_status', $processingStatus);
        }

        if (! empty($validated['event_type'])) {
            $query->where('event_type', $validated['event_type']);
        }

        if (array_key_exists('signature_valid', $validated) && $validated['signature_valid'] !== null) {
            $query->where('signature_valid', (bool) $validated['signature_valid']);
        }

        if (! empty($validated['id_transaksi'])) {
            $reference = $validated['id_transaksi'];
            $query->where(function (Builder $inner) use ($reference) {
                $inner->where('provider_transaction_id', $reference)
                    ->orWhere('id_order', $reference)
                    ->orWhere('idempotency_key', $reference);
            });
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('event_id', 'like', '%'.$search.'%')
                    ->orWhere('provider_transaction_id', 'like', '%'.$search.'%')
                    ->orWhere('id_order', 'like', '%'.$search.'%')
                    ->orWhere('idempotency_key', 'like', '%'.$search.'%');
            });
        }

        $logs = $query->paginate($this->perPage($validated))->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Daftar webhook log berhasil diambil.',
            'data' => $logs,
        ]);
    }

    public function webhookLogDetail(string $id): JsonResponse
    {
        $log = WebhookLog::find($id);

        if (! $log) {
            return $this->notFound('Webhook log tidak ditemukan.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail webhook log berhasil diambil.',
            'data' => $log,
        ]);
    }

    public function notificationLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'channel' => ['nullable', 'string', 'max:20'],
            'provider' => ['nullable', 'string', 'max:50'],
            'notification_type' => ['nullable', 'string', 'max:100'],
            'delivery_status' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:30'],
            'reference_id' => ['nullable', 'string', 'max:100'],
            'id_pengguna' => ['nullable', 'string', 'max:36'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = NotificationLog::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id_notification_log');

        $this->applyDateRange($query, $validated, 'created_at');

        foreach (['channel', 'provider', 'notification_type', 'id_pengguna'] as $field) {
            if (! empty($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        $deliveryStatus = $validated['delivery_status'] ?? $validated['status'] ?? null;

        if (! empty($deliveryStatus)) {
            $query->where('delivery_status', $deliveryStatus);
        }

        if (! empty($validated['reference_id'])) {
            $query->where('reference_id', $validated['reference_id']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('reference_id', 'like', '%'.$search.'%')
                    ->orWhere('recipient_masked', 'like', '%'.$search.'%')
                    ->orWhere('provider_message_id', 'like', '%'.$search.'%')
                    ->orWhere('error_message', 'like', '%'.$search.'%');
            });
        }

        $logs = $query->paginate($this->perPage($validated))->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Daftar notification log berhasil diambil.',
            'data' => $logs,
        ]);
    }

    public function notificationLogDetail(string $id): JsonResponse
    {
        $log = NotificationLog::find($id);

        if (! $log) {
            return $this->notFound('Notification log tidak ditemukan.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail notification log berhasil diambil.',
            'data' => $log,
        ]);
    }

    public function health(): JsonResponse
    {
        $database = $this->databaseCheck();
        $queue = $this->queueSummary((string) config('queue.default'));
        $storageWritable = is_writable(storage_path());

        $status = ! $database['ok']
            ? 'down'
            : (($queue['failed_jobs'] ?? 0) > 0 || ! $storageWritable ? 'degraded' : 'ok');

        return response()->json([
            'success' => $database['ok'],
            'message' => match ($status) {
                'ok' => 'Seluruh pemeriksaan sistem normal.',
                'degraded' => 'Sistem berjalan tetapi ada komponen yang perlu diperiksa.',
                default => 'Database tidak dapat diakses.',
            },
            'data' => [
                'status' => $status,
                'checked_at' => now()->toIso8601String(),
                'database' => $database,
                'queue' => $queue,
                'storage' => [
                    'writable' => $storageWritable,
                ],
            ],
        ], $database['ok'] ? 200 : 503);
    }

    public function failedJobs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'queue' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! $this->tableExists('failed_jobs')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel failed_jobs tidak tersedia pada database ini.',
            ], 503);
        }

        $query = DB::table('failed_jobs')->orderByDesc('failed_at');

        if (! empty($validated['queue'])) {
            $query->where('queue', $validated['queue']);
        }

        if (! empty($validated['start_date'])) {
            $query->where('failed_at', '>=', Carbon::parse($validated['start_date'])->startOfDay());
        }

        if (! empty($validated['end_date'])) {
            $query->where('failed_at', '<=', Carbon::parse($validated['end_date'])->endOfDay());
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($inner) use ($search) {
                $inner->where('uuid', 'like', '%'.$search.'%')
                    ->orWhere('exception', 'like', '%'.$search.'%');
            });
        }

        $jobs = $query->paginate($this->perPage($validated))
            ->withQueryString()
            ->through(fn ($row) => $this->presentFailedJob($row));

        return response()->json([
            'success' => true,
            'message' => 'Daftar failed job berhasil diambil.',
            'data' => $jobs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyDateRange(Builder $query, array $validated, string $column): void
    {
        if (! empty($validated['start_date'])) {
            $query->where($column, '>=', Carbon::parse($validated['start_date'])->startOfDay());
        }

        if (! empty($validated['end_date'])) {
            $query->where($column, '<=', Carbon::parse($validated['end_date'])->endOfDay());
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function perPage(array $validated): int
    {
        return (int) ($validated['per_page'] ?? 25);
    }

    /**
     * @return array<string, mixed>
     */
    private function queueSummary(string $connection): array
    {
        $driver = config("queue.connections.{$connection}.driver");
        $pending = null;
        $failed = null;
        $oldestPendingAt = null;

        try {
            if ($this->tableExists('jobs')) {
                $pending = (int) DB::table('jobs')->count();
                $oldest = DB::table('jobs')->min('available_at');

                if ($oldest !== null) {
                    $oldestPendingAt = Carbon::createFromTimestampUTC((int) $oldest)
                        ->toIso8601String();
                }
            }

            if ($this->tableExists('failed_jobs')) {
                $failed = (int) DB::table('failed_jobs')->count();
            }
        } catch (Throwable) {
            // Biarkan null: health check tetap mengembalikan data yang tersedia.
        }

        return [
            'connection' => $connection,
            'driver' => $driver,
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'oldest_pending_at' => $oldestPendingAt,
        ];
    }

    /**
     * @return array{ok: bool, driver: string, latency_ms: int|null}
     */
    private function databaseCheck(): array
    {
        $startedAt = microtime(true);

        try {
            DB::select('select 1');

            return [
                'ok' => true,
                'driver' => DB::connection()->getDriverName(),
                'latency_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            ];
        } catch (Throwable) {
            return [
                'ok' => false,
                'driver' => DB::connection()->getDriverName(),
                'latency_ms' => null,
            ];
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function presentFailedJob(object $row): array
    {
        $payload = json_decode((string) ($row->payload ?? ''), true);
        $exception = (string) ($row->exception ?? '');
        $firstLine = strtok($exception, "\n") ?: $exception;

        return [
            'id' => (int) $row->id,
            'uuid' => $row->uuid,
            'connection' => $row->connection,
            'queue' => $row->queue,
            'job' => is_array($payload) ? ($payload['displayName'] ?? null) : null,
            'attempts' => is_array($payload) ? ($payload['attempts'] ?? null) : null,
            'exception_excerpt' => mb_substr(
                $this->sanitizer->scrubText($firstLine),
                0,
                500
            ),
            'failed_at' => $row->failed_at !== null
                ? Carbon::parse($row->failed_at)->toIso8601String()
                : null,
        ];
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }
}
