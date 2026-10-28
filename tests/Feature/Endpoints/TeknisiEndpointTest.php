<?php

namespace Tests\Feature\Endpoints;

use App\Models\ApiAuditLog;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Services\MidtransService;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Notification;
use Mockery;

/**
 * Endpoint monitoring/troubleshooting role teknisi.
 */
class TeknisiEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Teknisi / Maintenance';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Otorisasi
    // ------------------------------------------------------------------

    public function test_otorisasi_role_teknisi(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/teknisi/dashboard', [
            'endpoint' => 'GET /api/teknisi/dashboard',
            'label' => 'Guest tidak boleh mengakses dashboard teknisi',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/teknisi/dashboard', [
            'endpoint' => 'GET /api/teknisi/dashboard',
            'label' => 'Pelanggan mendapat 403',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/teknisi/dashboard', [
            'endpoint' => 'GET /api/teknisi/dashboard',
            'label' => 'Admin mendapat 403 (bukan teknisi)',
            'expected' => 403,
        ]);

        $this->actingAsRole('teknisi');
        $this->hit('GET', '/api/teknisi/dashboard', [
            'endpoint' => 'GET /api/teknisi/dashboard',
            'label' => 'Teknisi boleh mengakses dashboard',
            'expected' => 200,
        ]);

        $this->assertSame(true, $this->recorded[3]['response']['success'] ?? null);
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Dashboard, audit log, sanitasi
    // ------------------------------------------------------------------

    public function test_dashboard_mengambil_ringkasan_data_nyata(): void
    {
        ApiAuditLog::create([
            'correlation_id' => 'corr-audit-1',
            'service_name' => 'payment',
            'http_method' => 'POST',
            'endpoint' => '/api/payments/midtrans/notification',
            'http_status' => 200,
            'latency_ms' => 12,
        ]);

        ApiAuditLog::create([
            'correlation_id' => 'corr-audit-2',
            'service_name' => 'payment',
            'http_method' => 'POST',
            'endpoint' => '/api/payments/checkout',
            'http_status' => 500,
            'error_code' => 'SERVER_ERROR',
            'latency_ms' => 99,
        ]);

        $this->makeWebhookLog(['processing_status' => 'processed', 'status' => 'diproses']);
        $this->makeWebhookLog(['processing_status' => 'failed', 'status' => 'gagal']);

        NotificationLog::create([
            'channel' => 'email',
            'provider' => 'smtp',
            'notification_type' => 'registration_otp',
            'delivery_status' => 'sent',
            'attempt_count' => 1,
            'sent_at' => now(),
        ]);
        NotificationLog::create([
            'channel' => 'email',
            'provider' => 'smtp',
            'notification_type' => 'order_paid',
            'delivery_status' => 'failed',
            'attempt_count' => 1,
            'error_message' => 'SMTP down',
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\TestJob']),
            'exception' => 'RuntimeException: gagal',
            'failed_at' => now(),
        ]);

        $this->actingAsRole('teknisi');

        $response = $this->hit('GET', '/api/teknisi/dashboard', [
            'endpoint' => 'GET /api/teknisi/dashboard',
            'label' => 'Ringkasan dashboard teknisi',
            'expected' => 200,
        ]);

        $data = $response->json('data');

        $this->assertGreaterThanOrEqual(2, $data['audit']['total_24h']);
        $this->assertGreaterThanOrEqual(1, $data['audit']['errors_24h']);
        $this->assertSame('payment', $data['audit']['top_services_24h'][0]['service_name']);
        $this->assertGreaterThanOrEqual(2, $data['webhooks']['received_24h']);
        $this->assertSame(1, $data['webhooks']['processed_24h']);
        $this->assertSame(1, $data['webhooks']['failed_24h']);
        $this->assertSame(1, $data['notifications']['sent_24h']);
        $this->assertSame(1, $data['notifications']['failed_24h']);
        $this->assertSame(1, $data['queue']['failed_jobs']);

        $this->assertRecorded();
    }

    public function test_middleware_audit_mencatat_error_dan_menyensor_data_sensitif(): void
    {
        $this->asGuest();

        $response = $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login gagal dicatat ke audit log',
            'json' => [
                'email' => 'tidak.ada@example.com',
                'kata_sandi' => 'rahasia-sekali',
                'otp' => '123456',
                'signature_key' => 'signature-rahasia',
            ],
            'headers' => [
                'X-Correlation-ID' => 'corr-login-gagal',
            ],
            'expected' => 401,
        ]);

        $this->assertSame('corr-login-gagal', $response->headers->get('X-Correlation-ID'));

        $log = ApiAuditLog::where('correlation_id', 'corr-login-gagal')->first();

        $this->assertNotNull($log);
        $this->assertSame(401, $log->http_status);
        $this->assertSame('UNAUTHENTICATED', $log->error_code);
        $this->assertSame('auth', $log->service_name);
        $this->assertSame('[REDACTED]', $log->request_summary['kata_sandi'] ?? null);
        $this->assertSame('[REDACTED]', $log->request_summary['otp'] ?? null);
        $this->assertSame('[REDACTED]', $log->request_summary['signature_key'] ?? null);

        $encoded = json_encode($log->request_summary);
        $this->assertStringNotContainsString('rahasia-sekali', (string) $encoded);
        $this->assertStringNotContainsString('signature-rahasia', (string) $encoded);
        $this->assertStringNotContainsString('123456', (string) $encoded);
    }

    public function test_audit_logs_pagination_filter_dan_detail(): void
    {
        foreach (range(1, 3) as $index) {
            ApiAuditLog::create([
                'correlation_id' => 'corr-'.$index,
                'service_name' => $index === 3 ? 'shipping' : 'payment',
                'http_method' => 'POST',
                'endpoint' => '/api/test/'.$index,
                'http_status' => $index === 2 ? 500 : 200,
                'error_code' => $index === 2 ? 'SERVER_ERROR' : null,
            ]);
        }

        $this->actingAsRole('teknisi');

        $response = $this->hit('GET', '/api/teknisi/audit-logs?per_page=2&page=1&endpoint=/api/test', [
            'endpoint' => 'GET /api/teknisi/audit-logs',
            'label' => 'Pagination audit log',
            'expected' => 200,
        ]);

        $this->assertSame(2, count($response->json('data.data')));
        $this->assertSame(3, $response->json('data.total'));
        $this->assertSame(2, $response->json('data.last_page'));

        $filtered = $this->hit('GET', '/api/teknisi/audit-logs?service_name=shipping&endpoint=/api/test', [
            'endpoint' => 'GET /api/teknisi/audit-logs',
            'label' => 'Filter service_name audit log',
            'expected' => 200,
        ]);

        $this->assertSame(1, $filtered->json('data.total'));
        $this->assertSame('shipping', $filtered->json('data.data.0.service_name'));

        $errors = $this->hit('GET', '/api/teknisi/audit-logs?status=error&endpoint=/api/test', [
            'endpoint' => 'GET /api/teknisi/audit-logs',
            'label' => 'Filter status error',
            'expected' => 200,
        ]);

        $this->assertSame(1, $errors->json('data.total'));

        $detail = $this->hit('GET', '/api/teknisi/audit-logs/'.$filtered->json('data.data.0.id_audit_log'), [
            'endpoint' => 'GET /api/teknisi/audit-logs/{id}',
            'label' => 'Detail audit log',
            'expected' => 200,
        ]);

        $this->assertSame('corr-3', $detail->json('data.correlation_id'));

        $this->hit('GET', '/api/teknisi/audit-logs/999999', [
            'endpoint' => 'GET /api/teknisi/audit-logs/{id}',
            'label' => 'Audit log tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Webhook log
    // ------------------------------------------------------------------

    public function test_webhook_sukses_dicatat_dan_duplikat_tidak_memproses_ulang(): void
    {
        $user = $this->makeUser();
        ['variant' => $variant] = $this->seedCatalog();
        $order = $this->makeOrder($user, [], [$variant]);
        $this->makePayment($order);

        $payload = [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-log-1',
            'gross_amount' => '150000.00',
            'status_code' => '200',
            'signature_key' => 'signature-sangat-rahasia',
        ];

        $this->mockMidtransWebhook($payload, true);

        $this->postJson('/api/payments/midtrans/notification', $payload)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $log = WebhookLog::latest('dibuat_pada')->first();

        $this->assertNotNull($log);
        $this->assertSame('midtrans', $log->provider);
        $this->assertTrue($log->signature_valid);
        $this->assertSame('processed', $log->processing_status);
        $this->assertSame('diproses', $log->status);
        $this->assertNull($log->signature);
        $this->assertNotNull($log->received_at);
        $this->assertNotNull($log->processed_at);
        $this->assertSame('trx-log-1', $log->provider_transaction_id);
        $this->assertSame('[REDACTED]', $log->payload_summary['signature_key'] ?? null);
        $this->assertStringNotContainsString(
            'signature-sangat-rahasia',
            (string) $log->payload
        );
        $this->assertStringNotContainsString(
            'signature-sangat-rahasia',
            (string) json_encode($log->payload_summary)
        );

        // Kirim ulang webhook identik: pembayaran tidak boleh diproses dua kali.
        $this->postJson('/api/payments/midtrans/notification', $payload)
            ->assertStatus(200)
            ->assertJsonPath('message', 'Notifikasi sudah pernah diproses.');

        $this->assertSame(1, Order::whereKey($order->id_order)->where('status_pesanan', 'dibayar')->count());
        $this->assertSame(1, Payment::where('id_order', $order->id_order)->count());
        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'provider_transaction_id' => 'trx-log-1',
            'processing_status' => 'duplicate',
        ]);
    }

    public function test_webhook_signature_invalid_dicatat_tanpa_memproses_pembayaran(): void
    {
        $user = $this->makeUser();
        ['variant' => $variant] = $this->seedCatalog();
        $order = $this->makeOrder($user, [], [$variant]);
        $this->makePayment($order);

        $payload = [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '150000.00',
            'status_code' => '200',
            'signature_key' => 'palsu',
        ];

        $this->mockMidtransWebhook($payload, false);

        $this->postJson('/api/payments/midtrans/notification', $payload)
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('webhook_logs', [
            'signature_valid' => false,
            'status' => 'ditolak',
            'processing_status' => 'rejected',
            'http_status' => 403,
        ]);

        $log = WebhookLog::latest('dibuat_pada')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->signature);
        $this->assertStringNotContainsString('palsu', (string) $log->payload);

        $this->assertSame('menunggu_pembayaran', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('payments', [
            'id_order' => $order->id_order,
            'status_pembayaran' => 'menunggu',
        ]);
    }

    public function test_webhook_logs_filter_dan_detail(): void
    {
        $this->makeWebhookLog([
            'provider' => 'midtrans',
            'event_type' => 'settlement',
            'processing_status' => 'processed',
            'signature_valid' => true,
            'provider_transaction_id' => 'trx-filter-1',
        ]);

        $this->makeWebhookLog([
            'provider' => 'midtrans',
            'event_type' => 'expire',
            'processing_status' => 'failed',
            'signature_valid' => true,
            'provider_transaction_id' => 'trx-filter-2',
        ]);

        $this->actingAsRole('teknisi');

        $response = $this->hit('GET', '/api/teknisi/webhook-logs?processing_status=processed', [
            'endpoint' => 'GET /api/teknisi/webhook-logs',
            'label' => 'Filter status webhook',
            'expected' => 200,
        ]);

        $this->assertSame(1, $response->json('data.total'));
        $this->assertSame('processed', $response->json('data.data.0.processing_status'));

        $search = $this->hit('GET', '/api/teknisi/webhook-logs?id_transaksi=trx-filter-2', [
            'endpoint' => 'GET /api/teknisi/webhook-logs',
            'label' => 'Cari webhook berdasarkan transaksi',
            'expected' => 200,
        ]);

        $this->assertSame('trx-filter-2', $search->json('data.data.0.provider_transaction_id'));

        $detail = $this->hit('GET', '/api/teknisi/webhook-logs/'.$search->json('data.data.0.id_webhook_log'), [
            'endpoint' => 'GET /api/teknisi/webhook-logs/{id}',
            'label' => 'Detail webhook log',
            'expected' => 200,
        ]);

        $this->assertSame('failed', $detail->json('data.processing_status'));
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Notification log
    // ------------------------------------------------------------------

    public function test_notification_otp_tercatat_tanpa_menyimpan_otp(): void
    {
        $user = $this->makeUser();

        /** @var OtpService $otp */
        $otp = app(OtpService::class);
        $this->assertTrue($otp->sendRegistrationOtp($user));

        $log = NotificationLog::where('notification_type', 'registration_otp')
            ->latest('id_notification_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('email', $log->channel);
        $this->assertSame($user->id_user, $log->id_pengguna);
        $this->assertNotSame($user->email, $log->recipient_masked);
        $this->assertStringContainsString('*', (string) $log->recipient_masked);
        $this->assertContains($log->delivery_status, ['queued', 'sent']);
        $this->assertNull($log->error_message);

        $columns = array_keys((array) DB::table('notification_logs')->first());
        $this->assertNotContains('otp', $columns);
        $this->assertNotContains('password', $columns);
    }

    public function test_notification_logs_pagination_filter_dan_detail(): void
    {
        foreach ([
            ['registration_otp', 'sent'],
            ['order_paid', 'failed'],
            ['password_reset_otp', 'queued'],
        ] as $index => [$type, $status]) {
            NotificationLog::create([
                'channel' => 'email',
                'provider' => 'mailjet',
                'notification_type' => $type,
                'recipient_masked' => 'u***@example.com',
                'reference_type' => 'user',
                'reference_id' => 'ref-'.$index,
                'delivery_status' => $status,
                'attempt_count' => $index,
            ]);
        }

        $this->actingAsRole('teknisi');

        $response = $this->hit('GET', '/api/teknisi/notification-logs?per_page=2', [
            'endpoint' => 'GET /api/teknisi/notification-logs',
            'label' => 'Pagination notification log',
            'expected' => 200,
        ]);

        $this->assertSame(2, count($response->json('data.data')));
        $this->assertSame(3, $response->json('data.total'));

        $filtered = $this->hit('GET', '/api/teknisi/notification-logs?delivery_status=failed', [
            'endpoint' => 'GET /api/teknisi/notification-logs',
            'label' => 'Filter status notifikasi',
            'expected' => 200,
        ]);

        $this->assertSame(1, $filtered->json('data.total'));
        $this->assertSame('order_paid', $filtered->json('data.data.0.notification_type'));

        $detail = $this->hit('GET', '/api/teknisi/notification-logs/'.$filtered->json('data.data.0.id_notification_log'), [
            'endpoint' => 'GET /api/teknisi/notification-logs/{id}',
            'label' => 'Detail notification log',
            'expected' => 200,
        ]);

        $this->assertSame('failed', $detail->json('data.delivery_status'));

        $this->hit('GET', '/api/teknisi/notification-logs/999999', [
            'endpoint' => 'GET /api/teknisi/notification-logs/{id}',
            'label' => 'Notification log tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Health & failed jobs
    // ------------------------------------------------------------------

    public function test_health_tidak_membocorkan_kredensial(): void
    {
        $this->actingAsRole('teknisi');

        $response = $this->hit('GET', '/api/teknisi/health', [
            'endpoint' => 'GET /api/teknisi/health',
            'label' => 'Health check database dan queue',
            'expected' => 200,
        ]);

        $this->assertSame('ok', $response->json('data.status'));
        $this->assertTrue($response->json('data.database.ok'));
        $this->assertSame('sqlite', $response->json('data.database.driver'));
        $this->assertTrue($response->json('data.storage.writable'));

        $body = mb_strtolower((string) $response->getContent());
        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('server_key', $body);
        $this->assertStringNotContainsString('api_key', $body);
        $this->assertStringNotContainsString('db_database', $body);

        $this->assertRecorded();
    }

    public function test_failed_jobs_pagination_filter_dan_sanitasi(): void
    {
        foreach (range(1, 2) as $index) {
            DB::table('failed_jobs')->insert([
                'uuid' => 'uuid-'.$index,
                'connection' => 'database',
                'queue' => $index === 2 ? 'email' : 'default',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\Job'.$index,
                    'attempts' => $index,
                ]),
                'exception' => "RuntimeException: gagal token=rahasia{$index}\n#0 stack trace",
                'failed_at' => now(),
            ]);
        }

        $this->actingAsRole('teknisi');

        $response = $this->hit('GET', '/api/teknisi/failed-jobs?per_page=1&queue=email', [
            'endpoint' => 'GET /api/teknisi/failed-jobs',
            'label' => 'Pagination & filter failed job',
            'expected' => 200,
        ]);

        $this->assertSame(1, $response->json('data.total'));
        $this->assertSame('App\\Jobs\\Job2', $response->json('data.data.0.job'));
        $this->assertSame('email', $response->json('data.data.0.queue'));
        $this->assertStringNotContainsString(
            "\n",
            (string) $response->json('data.data.0.exception_excerpt')
        );
        $this->assertStringNotContainsString(
            'token=rahasia2',
            (string) $response->json('data.data.0.exception_excerpt')
        );

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeWebhookLog(array $overrides = []): WebhookLog
    {
        return WebhookLog::create(array_merge([
            'id_webhook_log' => (string) Str::uuid(),
            'provider' => 'midtrans',
            'endpoint' => '/api/payments/midtrans/notification',
            'method' => 'POST',
            'payload' => '{}',
            'signature' => null,
            'signature_valid' => true,
            'status' => 'diterima',
            'processing_status' => 'received',
            'payload_summary' => [],
            'attempt_count' => 1,
            'received_at' => now(),
            'http_status' => 200,
            'alamat_ip' => '127.0.0.1',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mockMidtransWebhook(array $payload, bool $signatureValid): void
    {
        $reflection = new \ReflectionClass(Notification::class);
        $notification = $reflection->newInstanceWithoutConstructor();
        $response = $reflection->getProperty('response');
        $response->setAccessible(true);
        $response->setValue($notification, (object) $payload);

        $this->mock(MidtransService::class, function ($mock) use ($notification, $signatureValid) {
            $mock->shouldReceive('verifySignature')->andReturn($signatureValid);
            $mock->shouldReceive('notification')->andReturn($notification);
        });
    }
}
