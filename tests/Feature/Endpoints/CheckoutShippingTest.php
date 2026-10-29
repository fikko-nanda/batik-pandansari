<?php

namespace Tests\Feature\Endpoints;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProfilUsaha;
use App\Models\ZonaPengiriman;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CheckoutShippingTest extends EndpointTestCase
{
    protected static string $group = 'Checkout Ongkir';

    public function test_checkout_alerts_with_clear_error_and_preserves_cart_when_origin_is_not_configured(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant']);
        ProfilUsaha::updateOrCreate(
            ['id_profil_usaha' => 1],
            [
                'nama_usaha' => 'Batik Pandansari',
                'alamat_lengkap' => 'Wonokerto, Jembangan, Sragen',
                'latitude' => null,
                'longitude' => null,
            ]
        );
        Http::preventStrayRequests();

        $this->hit('POST', '/api/checkout', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 503,
        ]);

        $this->assertSame('SHIPPING_ORIGIN_NOT_CONFIGURED', $this->recorded[0]['response']['code'] ?? null);
        $this->assertSame(
            'Lokasi asal pengiriman belum dikonfigurasi oleh admin.',
            $this->recorded[0]['response']['message'] ?? null
        );
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('aktif', Cart::where('id_user', $user->id_user)->value('status_cart'));
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_checkout_persists_shipping_snapshot_and_passes_same_total_to_payment(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $catalog['variant']->update(['berat_gram' => 1001]);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant']);
        ProfilUsaha::create([
            'id_profil_usaha' => 1,
            'nama_usaha' => 'Batik Pandansari',
            'alamat_lengkap' => 'Sragen',
            'latitude' => -7.445,
            'longitude' => 110.89,
        ]);
        ZonaPengiriman::create([
            'id_zona' => (string) Str::uuid(),
            'nama_zona' => 'Zona Uji',
            'jarak_min_km' => 0,
            'jarak_maks_km' => 100,
            'biaya_dasar' => 5000,
            'biaya_per_kg' => 2000,
            'status_aktif' => true,
        ]);
        Cache::flush();
        Http::preventStrayRequests();
        $transactionLevelDuringRouting = null;
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => function () use (
                &$transactionLevelDuringRouting
            ) {
                $transactionLevelDuringRouting = DB::transactionLevel();

                return Http::response([
                    'code' => 'Ok',
                    'routes' => [[
                        'distance' => 9999,
                        'duration' => 900,
                    ]],
                ]);
            },
        ]);

        $this->hit('POST', '/api/checkout', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 201,
        ]);

        $orderData = $this->recorded[0]['response']['data'] ?? [];
        $order = Order::findOrFail($orderData['id_order']);
        $this->assertSame(0, $transactionLevelDuringRouting);
        $this->assertSame(9000.0, (float) $order->biaya_pengiriman);
        $this->assertSame(9.999, (float) $order->jarak_km);
        $this->assertSame('Zona Uji', $order->zona_pengiriman);
        $this->assertSame(1001, (int) $order->berat_pengiriman_gram);
        $this->assertSame(175500.0, (float) $order->total_pembayaran);
        $this->assertSame([], $this->recorded[0]['db_effect']['payments'] ?? []);
        Http::assertSentCount(1);

        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
        ]);
        $this->mock(MidtransService::class, function ($mock): void {
            $mock->shouldReceive('createSnapTransaction')
                ->once()
                ->withArgs(fn (Order $passedOrder): bool => (float) $passedOrder->total_pembayaran === 175500.0)
                ->andReturn([
                    'token' => 'snap-test-token',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-test-token',
                    'raw' => [],
                ]);
            $mock->shouldReceive('clientKey')->once()->andReturn('SB-Mid-client-test');
        });

        $this->hit('POST', '/api/orders/'.$order->id_order.'/payment', [
            'expected' => 201,
        ]);

        $paymentData = $this->recorded[1]['response']['data'] ?? [];
        $payment = Payment::where('id_order', $order->id_order)->firstOrFail();
        $this->assertSame(175500.0, (float) $payment->jumlah_pembayaran);
        $this->assertSame(175500.0, (float) ($paymentData['jumlah_pembayaran'] ?? 0));
        $this->assertSame('snap-test-token', $paymentData['snap_token'] ?? null);
        $this->assertDatabaseHas('orders', [
            'id_order' => $order->id_order,
            'biaya_pengiriman' => 9000,
            'zona_pengiriman' => 'Zona Uji',
            'berat_pengiriman_gram' => 1001,
        ]);
        $this->assertRecorded();
    }

    public function test_checkout_returns_clear_error_without_mutating_data_when_zone_is_missing(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant']);
        $this->seedShippingBaseline(5.0, ['jarak_maks_km' => 3]);
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 5000, 'duration' => 900]],
            ]),
        ]);

        $this->hit('POST', '/api/checkout', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 422,
        ]);

        $this->assertSame('SHIPPING_ZONE_NOT_FOUND', $this->recorded[0]['response']['code'] ?? null);
        $this->assertStringContainsString('Zona pengiriman', $this->recorded[0]['response']['message'] ?? '');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, (int) $catalog['variant']->fresh()->stok_dipesan);
        $this->assertSame(
            'aktif',
            Cart::where('id_user', $user->id_user)->value('status_cart')
        );
        $this->assertRecorded();
    }

    public function test_checkout_can_sell_all_physical_stock_and_reduce_it_to_zero_after_payment(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(3, 150000);
        $legacyOrder = $this->makeOrder(
            $user,
            ['nomor_pesanan' => 'ORD-STOCK-LEGACY'],
            [$catalog['variant']]
        );
        $legacyOrder->update(['stok_direservasi' => true]);
        $catalog['variant']->update(['stok_dipesan' => 1]);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 3);
        $this->seedShippingBaseline();
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 5000, 'duration' => 900]],
            ]),
        ]);

        $this->hit('POST', '/api/checkout', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 201,
        ]);

        $order = Order::findOrFail($this->recorded[0]['response']['data']['id_order']);
        $this->assertSame(3, (int) $catalog['variant']->fresh()->stok);
        $this->assertSame(1, (int) $catalog['variant']->fresh()->stok_dipesan);
        $this->assertFalse((bool) $order->stok_direservasi);

        $orders = app(OrderService::class);
        $orders->changeStatus($order, 'dibayar');

        $this->assertSame(0, (int) $catalog['variant']->fresh()->stok);
        $this->assertSame(1, (int) $catalog['variant']->fresh()->stok_dipesan);
        $this->assertDatabaseHas('inventory_movements', [
            'referensi' => $order->nomor_pesanan,
            'jumlah' => 3,
            'stok_sebelum' => 3,
            'stok_sesudah' => 0,
        ]);

        $orders->changeStatus($legacyOrder, 'dibayar');

        $this->assertSame(0, (int) $catalog['variant']->fresh()->stok);
        $this->assertSame(0, (int) $catalog['variant']->fresh()->stok_dipesan);
        $this->assertTrue(
            OrderStatusHistory::where('id_order', $legacyOrder->id_order)
                ->where('catatan', 'like', '%perlu tindak lanjut admin/refund%')
                ->exists()
        );
        $this->assertRecorded();
    }

    public function test_payment_shortage_does_not_make_stock_negative_and_records_admin_warning(): void
    {
        $admin = $this->actingAsRole('admin');
        $customer = $this->makeUser('pelanggan');
        $catalog = $this->seedCatalog(3, 150000);
        $firstOrder = $this->makeOrder(
            $customer,
            ['nomor_pesanan' => 'ORD-STOCK-FIRST'],
            [$catalog['variant']]
        );
        $secondOrder = $this->makeOrder(
            $customer,
            ['nomor_pesanan' => 'ORD-STOCK-SECOND'],
            [$catalog['variant']]
        );
        $firstOrder->items()->update(['jumlah' => 2]);
        $secondOrder->items()->update(['jumlah' => 2]);

        $orders = app(OrderService::class);
        $orders->changeStatus($firstOrder, 'dibayar', $admin->id_user);
        $orders->changeStatus($secondOrder, 'dibayar', $admin->id_user);

        $this->assertSame(1, (int) $catalog['variant']->fresh()->stok);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertTrue(
            OrderStatusHistory::where('id_order', $secondOrder->id_order)
                ->where('status_baru', 'dibayar')
                ->where('catatan', 'like', '%perlu tindak lanjut admin/refund%')
                ->exists()
        );

        $orders->changeStatus($secondOrder, 'dibatalkan', $admin->id_user);

        $this->assertSame(1, (int) $catalog['variant']->fresh()->stok);
        $this->assertDatabaseCount('inventory_movements', 1);
    }
}
