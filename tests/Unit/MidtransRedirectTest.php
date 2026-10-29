<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\TestCase;
use Midtrans\Config;
use Midtrans\MT_Tests;

/**
 * Memastikan transaksi Snap memakai URL pengalihan (callbacks) milik sendiri
 * dan tidak lagi mengarah ke example.com bawaan akun Midtrans.
 *
 * SDK Midtrans memakai cURL mentah; kelas MT_Tests bawaan SDK menyediakan
 * mekanisme stub yang merekam argumen curl terakhir tanpa benar-benar
 * memanggil jaringan. Berkas MT_Tests berada di direktori tests SDK sehingga
 * perlu di-require manual.
 */
class MidtransRedirectTest extends TestCase
{
    private bool $tersediaStub = false;

    protected function setUp(): void
    {
        parent::setUp();

        $path = base_path('vendor/midtrans/midtrans-php/tests/MT_Tests.php');

        if (is_file($path)) {
            require_once $path;
            $this->tersediaStub = class_exists(MT_Tests::class);
        }
    }

    protected function tearDown(): void
    {
        if ($this->tersediaStub) {
            MT_Tests::reset();
        }

        Config::$isProduction = false;
        Config::$serverKey = '';
        Config::$clientKey = '';

        parent::tearDown();
    }

    public function test_callbacks_snap_memakai_url_aplikasi_bukan_example_com(): void
    {
        config()->set('services.midtrans.server_key', 'SB-Mid-server-dummy');
        config()->set('services.midtrans.client_key', 'SB-Mid-client-dummy');
        config()->set('services.midtrans.is_production', false);
        config()->set('services.midtrans.finish_redirect_url', 'http://localhost:8000/pembayaran/selesai');
        config()->set('services.midtrans.unfinish_redirect_url', 'http://localhost:8000/pembayaran/tertunda');
        config()->set('services.midtrans.error_redirect_url', 'http://localhost:8000/pembayaran/gagal');

        $this->skipBilaStubTidakAda();

        MT_Tests::$stubHttp = true;
        MT_Tests::$stubHttpResponse = '{"token":"test-token","redirect_url":"https://app.sandbox.midtrans.com/snap/v4/redirection/test-token"}';
        MT_Tests::$stubHttpStatus = ['http_code' => 201];

        $this->travelTo(now()->startOfMinute());
        $service = new MidtransService;
        $order = $this->buatOrder();
        $order->kedaluwarsa_pada = now()->addMinutes(15);
        $service->createSnapTransaction($order);

        $requestOptions = MT_Tests::$lastHttpRequest['curl'] ?? [];
        $body = json_decode($requestOptions[CURLOPT_POSTFIELDS] ?? '{}', true);

        $this->assertIsArray($body);
        $this->assertSame('minute', $body['expiry']['unit'] ?? null);
        $this->assertSame(15, $body['expiry']['duration'] ?? null);
        $this->assertArrayHasKey('callbacks', $body);
        $this->assertSame('ORD-UJI-0001', $body['transaction_details']['order_id'] ?? null);
        $this->assertSame(175500, $body['transaction_details']['gross_amount'] ?? null);

        $this->assertSame(
            'http://localhost:8000/pembayaran/selesai',
            $body['callbacks']['finish'] ?? null
        );
        $this->assertSame(
            'http://localhost:8000/pembayaran/tertunda',
            $body['callbacks']['unfinish'] ?? null
        );
        $this->assertSame(
            'http://localhost:8000/pembayaran/gagal',
            $body['callbacks']['error'] ?? null
        );

        foreach ($body['callbacks'] as $url) {
            $this->assertStringNotContainsString('example.com', (string) $url);
        }
    }

    public function test_default_callbacks_mengarah_ke_route_pembayaran_yang_benar(): void
    {
        // Tanpa override env, config harus jatuh ke APP_URL + route pembayaran.
        $finish = config('services.midtrans.finish_redirect_url');
        $unfinish = config('services.midtrans.unfinish_redirect_url');
        $error = config('services.midtrans.error_redirect_url');

        $this->assertStringEndsWith('/pembayaran/selesai', (string) $finish);
        $this->assertStringEndsWith('/pembayaran/tertunda', (string) $unfinish);
        $this->assertStringEndsWith('/pembayaran/gagal', (string) $error);

        $this->assertStringNotContainsString('example.com', (string) $finish);
        $this->assertStringNotContainsString('example.com', (string) $unfinish);
        $this->assertStringNotContainsString('example.com', (string) $error);
    }

    private function skipBilaStubTidakAda(): void
    {
        if (! $this->tersediaStub) {
            $this->markTestSkipped('Kelas MT_Tests bawaan SDK Midtrans tidak tersedia untuk stub HTTP.');
        }
    }

    private function buatOrder(): Order
    {
        $order = new Order;
        $order->nomor_pesanan = 'ORD-UJI-0001';
        $order->total_pembayaran = 175500;
        $order->nama_penerima = 'Pelanggan Uji';
        $order->nomor_telepon_penerima = '081234567890';
        $order->alamat_pengiriman = 'Jl. Batik No. 1';
        $order->setRelation('user', null);

        return $order;
    }
}
