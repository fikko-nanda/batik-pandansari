<?php

namespace Tests\Feature\Endpoints;

use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Publikasi voucher & ringkasan voucher (admin):
 * POST /api/vouchers/{id}/publish
 * POST /api/vouchers/{id}/unpublish
 * GET  /api/vouchers/summary
 */
class AdminVoucherPromoEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Voucher Promo (Admin)';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_promo_voucher_akses(): void
    {
        $voucher = $this->makeVoucher(['status_voucher' => 'nonaktif']);

        $this->asGuest();
        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/vouchers/summary', [
            'endpoint' => 'GET /api/vouchers/summary',
            'label' => 'Ringkasan voucher tanpa token (401)',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/unpublish", [
            'endpoint' => 'POST /api/vouchers/{id}/unpublish',
            'label' => 'Tarik voucher sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('GET', '/api/vouchers/summary', [
            'endpoint' => 'GET /api/vouchers/summary',
            'label' => 'Ringkasan voucher sebagai pelanggan (403)',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/vouchers/summary', [
            'endpoint' => 'GET /api/vouchers/summary',
            'label' => 'Ringkasan voucher sebagai admin (200)',
            'expected' => 200,
        ]);

        $this->assertSame('nonaktif', Voucher::whereKey($voucher->id_voucher)->value('status_voucher'), 'Akses ditolak tidak boleh mengubah data.');

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Publish / unpublish
    // ------------------------------------------------------------------

    public function test_voucher_publish_sukses(): void
    {
        $this->actingAsRole('admin');
        $voucher = $this->makeVoucher(['status_voucher' => 'nonaktif']);

        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher nonaktif (200, status -> aktif)',
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertSame('aktif', $response['data']['status_voucher'] ?? null);
        $this->assertSame('aktif', Voucher::whereKey($voucher->id_voucher)->value('status_voucher'), 'Status harus benar-benar tersimpan.');

        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher yang sudah aktif (idempoten, 200)',
            'expected' => 200,
        ]);
        $this->assertSame('aktif', $this->recorded[1]['response']['data']['status_voucher'] ?? null);

        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/unpublish", [
            'endpoint' => 'POST /api/vouchers/{id}/unpublish',
            'label' => 'Tarik voucher aktif dari publikasi (200, status -> nonaktif)',
            'expected' => 200,
        ]);

        $this->assertSame('nonaktif', Voucher::whereKey($voucher->id_voucher)->value('status_voucher'), 'Hasil unpublish harus tersimpan.');

        $this->assertRecorded();
    }

    public function test_voucher_publish_ditolak_kadaluarsa(): void
    {
        $this->actingAsRole('admin');
        $voucher = $this->makeVoucher([
            'status_voucher' => 'nonaktif',
            'tanggal_mulai' => Carbon::now()->subDays(10),
            'tanggal_selesai' => Carbon::now()->subDay(),
        ]);

        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher yang sudah kedaluwarsa (422)',
            'expected' => 422,
        ]);

        $this->assertSame('nonaktif', Voucher::whereKey($voucher->id_voucher)->value('status_voucher'), 'Voucher kadaluarsa tidak boleh berubah menjadi aktif.');
        $this->assertRecorded();
    }

    public function test_voucher_promo_id_tidak_ditemukan(): void
    {
        $this->actingAsRole('admin');
        $id = '00000000-0000-4000-8000-000000000000';

        $this->hit('POST', "/api/vouchers/{$id}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher dengan ID tidak ada (404)',
            'expected' => 404,
        ]);
        $this->hit('POST', "/api/vouchers/{$id}/unpublish", [
            'endpoint' => 'POST /api/vouchers/{id}/unpublish',
            'label' => 'Tarik voucher dengan ID tidak ada (404)',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_voucher_per_produk_store_dan_publish(): void
    {
        $this->actingAsRole('admin');
        $this->seedCategory();
        $product = $this->makeProduct('Batik Peruntukan');
        $this->makeVariant($product, 10, 'aktif', 125000);
        $productNonaktif = $this->makeProduct('Batik Nonaktif', 'nonaktif');

        $payload = [
            'kode_voucher' => 'PRODUKADM',
            'nama_voucher' => 'Promo Produk',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(7)->toDateString(),
            'id_product_peruntukan' => $product->id_product,
        ];

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher untuk produk aktif (201)',
            'json' => $payload,
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($product->id_product, $data['id_product_peruntukan'] ?? null);
        $this->assertSame('Batik Peruntukan', $data['nama_produk_peruntukan'] ?? null);
        $this->assertSame('aktif', $data['status_produk_peruntukan'] ?? null);

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher untuk produk nonaktif (422)',
            'json' => array_merge($payload, [
                'kode_voucher' => 'PRODUKNON',
                'id_product_peruntukan' => $productNonaktif->id_product,
            ]),
            'expected' => 422,
        ]);

        $this->assertStringContainsString(
            'Voucher hanya berlaku untuk produk Batik Nonaktif.',
            (string) ($this->recorded[1]['response']['message'] ?? '')
        );

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher dengan id produk peruntukan tidak ditemukan (422)',
            'json' => array_merge($payload, [
                'kode_voucher' => 'PRODUKTIDAK',
                'id_product_peruntukan' => (string) Str::uuid(),
            ]),
            'expected' => 422,
        ]);

        $this->assertStringContainsString(
            'id_product_peruntukan tidak ditemukan.',
            implode(' ', (array) ($this->recorded[2]['response']['errors']['id_product_peruntukan'] ?? []))
        );

        $voucher = $this->makeVoucher([
            'status_voucher' => 'nonaktif',
            'id_product_peruntukan' => $productNonaktif->id_product,
        ]);

        $this->hit('POST', "/api/vouchers/{$voucher->id_voucher}/publish", [
            'endpoint' => 'POST /api/vouchers/{id}/publish',
            'label' => 'Publikasikan voucher produk nonaktif ditolak (422)',
            'expected' => 422,
        ]);

        $this->assertSame(
            'nonaktif',
            Voucher::whereKey($voucher->id_voucher)->value('status_voucher'),
            'Voucher produk nonaktif tidak boleh dipublikasikan.'
        );

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/vouchers/summary
    // ------------------------------------------------------------------

    public function test_voucher_summary_akurasi(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();

        // 4 voucher: 1 berjalan, 1 nonaktif, 1 terjadwal, 1 kadaluarsa.
        $this->makeVoucher([
            'tanggal_mulai' => Carbon::now()->subDay(),
            'tanggal_selesai' => Carbon::now()->addDay(),
        ]);
        $this->makeVoucher(['status_voucher' => 'nonaktif']);
        $this->makeVoucher([
            'tanggal_mulai' => Carbon::now()->addDay(),
            'tanggal_selesai' => Carbon::now()->addDays(2),
        ]);
        $this->makeVoucher([
            'tanggal_mulai' => Carbon::now()->subDays(5),
            'tanggal_selesai' => Carbon::now()->subDay(),
        ]);

        // 4 penggunaan: 2 digunakan (diskon 10.000 & 25.000), 1 menunggu, 1 dibatalkan.
        $digunakanA = $this->makeVoucher();
        $this->seedUsage($digunakanA, $user->id_user, 'digunakan', 10000);
        $this->seedUsage($digunakanA, $this->makeUser()->id_user, 'digunakan', 25000);
        $this->seedUsage($digunakanA, $this->makeUser()->id_user, 'menunggu', 5000);
        $this->seedUsage($digunakanA, $this->makeUser()->id_user, 'dibatalkan', 7000);

        $this->hit('GET', '/api/vouchers/summary', [
            'endpoint' => 'GET /api/vouchers/summary',
            'label' => 'Ringkasan voucher admin (data benar-benar dihitung dari database)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame(5, $data['total_voucher'] ?? null);
        $this->assertSame(4, $data['aktif'] ?? null);
        $this->assertSame(1, $data['nonaktif'] ?? null);
        $this->assertSame(2, $data['berjalan'] ?? null);
        $this->assertSame(1, $data['terjadwal'] ?? null);
        $this->assertSame(1, $data['kadaluarsa'] ?? null);
        $this->assertSame(2, $data['total_penggunaan'] ?? null);
        $this->assertEquals(35000, $data['total_diskon_diberikan'] ?? null);

        $this->assertRecorded();
    }

    private function seedUsage(Voucher $voucher, string $idUser, string $status, float $diskon): void
    {
        VoucherUsage::create([
            'id_voucher_usage' => (string) Str::uuid(),
            'id_voucher' => $voucher->id_voucher,
            'id_user' => $idUser,
            'id_order' => (string) Str::uuid(),
            'jumlah_diskon' => $diskon,
            'status_penggunaan' => $status,
            'digunakan_pada' => $status === 'digunakan' ? now() : null,
        ]);
    }
}
