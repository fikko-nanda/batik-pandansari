<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CmsGaleri;
use App\Models\CmsGambarGaleri;
use App\Models\CmsKategori;
use App\Models\CmsPromo;
use App\Models\FasilitasUsaha;
use App\Models\ProductImage;
use App\Models\ProfilUsaha;
use App\Models\SlideHero;
use App\Models\Voucher;
use App\Services\ImageUploadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Konten CMS halaman utama (publik).
 *
 * GET /api/cms/promo      satu voucher promo unggulan
 * GET /api/cms/promos     daftar promo unggulan yang dikonfigurasi
 * GET /api/cms/homepage   agregat seluruh konten beranda
 *
 * Endpoint promo jamak memakai konfigurasi CMS dan hanya menampilkan voucher
 * aktif yang masih dalam periode. Endpoint tunggal tetap mempertahankan
 * pemilihan otomatis lama sebagai kompatibilitas.
 */
class CmsHomepageController extends Controller
{
    private const SINGLETON_GALERI = 1;

    private const SINGLETON_PROFIL = 1;

    private const SINGLETON_PROMO = 1;

    public function __construct(private readonly ImageUploadService $images) {}

    public function promo(): JsonResponse
    {
        $promo = $this->resolvePromo();

        return response()->json([
            'success' => true,
            'message' => $promo ? 'Promo berhasil diambil.' : 'Belum ada promo aktif.',
            'data' => $promo,
        ]);
    }

    public function promos(): JsonResponse
    {
        $promos = $this->resolvePromos();

        return response()->json([
            'success' => true,
            'message' => $promos ? 'Daftar promo berhasil diambil.' : 'Belum ada promo aktif.',
            'data' => $promos,
        ]);
    }

    public function homepage(): JsonResponse
    {
        $promo = $this->resolvePromo();

        return response()->json([
            'success' => true,
            'message' => 'Konten halaman utama berhasil diambil.',
            'data' => [
                'hero' => $this->hero(),
                'kategori' => $this->kategori(),
                'promo' => $promo,
                'galeri' => $this->galeri(),
                'profil_usaha' => $this->profil(),
                'fasilitas' => $this->fasilitas(),
            ],
        ]);
    }

    /**
     * Mengambil promo unggulan: utamakan pin di cms_promo bila aktif dan valid,
     * selain itu fallback ke voucher aktif terbaru (perilaku lama).
     *
     * @return array<string, mixed>|null
     */
    private function resolvePromo(): ?array
    {
        $now = Carbon::now();
        $promoConfig = CmsPromo::find(self::SINGLETON_PROMO);

        if ($promoConfig && $promoConfig->id_voucher) {
            $pinned = Voucher::query()
                ->with([
                    'productPeruntukan.variants' => fn ($query) => $query
                        ->where('status_varian', 'aktif')
                        ->where('stok', '>', 0),
                    'productPeruntukan.images' => fn ($query) => $query
                        ->where('type', ProductImage::TYPE_PRODUK)
                        ->orderByDesc('is_primary')
                        ->orderBy('urutan'),
                ])
                ->where('id_voucher', $promoConfig->id_voucher)
                ->where('status_voucher', 'aktif')
                ->where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->where(function ($query) {
                    $query->whereNull('id_product_peruntukan')
                        ->orWhereHas(
                            'productPeruntukan',
                            fn ($inner) => $inner
                                ->where('status_produk', 'aktif')
                                ->whereHas('variants', fn ($query) => $query
                                    ->where('status_varian', 'aktif')
                                    ->where('stok', '>', 0))
                        );
                })
                ->first();

            if ($pinned) {
                return $this->presentPromoVoucher($pinned, $promoConfig);
            }
        }

        $fallback = $this->featuredVoucher();

        return $fallback ? $this->presentPromoVoucher($fallback, null) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolvePromos(): array
    {
        $now = Carbon::now();

        return CmsPromo::query()
            ->with([
                'voucher.productPeruntukan.variants' => fn ($query) => $query
                    ->where('status_varian', 'aktif')
                    ->where('stok', '>', 0),
                'voucher.productPeruntukan.images' => fn ($query) => $query
                    ->where('type', ProductImage::TYPE_PRODUK)
                    ->orderByDesc('is_primary')
                    ->orderBy('urutan'),
            ])
            ->whereHas('voucher', fn ($query) => $query
                ->where('status_voucher', 'aktif')
                ->where('tanggal_mulai', '<=', $now)
                ->where('tanggal_selesai', '>=', $now)
                ->where(function ($target) {
                    $target->whereNull('id_product_peruntukan')
                        ->orWhereHas(
                            'productPeruntukan',
                            fn ($product) => $product
                                ->where('status_produk', 'aktif')
                                ->whereHas('variants', fn ($query) => $query
                                    ->where('status_varian', 'aktif')
                                    ->where('stok', '>', 0))
                        );
                }))
            ->orderBy('dibuat_pada', 'desc')
            ->orderBy('id_cms_promo', 'desc')
            ->get()
            ->map(fn (CmsPromo $promo) => $this->presentPromoVoucher($promo->voucher, $promo))
            ->all();
    }

    private function featuredVoucher(): ?Voucher
    {
        $now = Carbon::now();

        return Voucher::query()
            ->with([
                'productPeruntukan.variants' => fn ($query) => $query
                    ->where('status_varian', 'aktif')
                    ->where('stok', '>', 0),
                'productPeruntukan.images' => fn ($query) => $query
                    ->where('type', ProductImage::TYPE_PRODUK)
                    ->orderByDesc('is_primary')
                    ->orderBy('urutan'),
            ])
            ->where('status_voucher', 'aktif')
            ->where('tanggal_mulai', '<=', $now)
            ->where('tanggal_selesai', '>=', $now)
            ->where(function ($query) {
                $query->whereNull('id_product_peruntukan')
                    ->orWhereHas(
                        'productPeruntukan',
                        fn ($inner) => $inner
                            ->where('status_produk', 'aktif')
                            ->whereHas('variants', fn ($query) => $query
                                ->where('status_varian', 'aktif')
                                ->where('stok', '>', 0))
                    );
            })
            ->orderByRaw('CASE WHEN gambar IS NULL OR gambar = ? THEN 1 ELSE 0 END', [''])
            ->orderByDesc('dibuat_pada')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPromoVoucher(Voucher $voucher, ?CmsPromo $promoConfig = null): array
    {
        $product = $voucher->productPeruntukan;
        $hargaMulai = $product?->variants
            ->min(fn ($variant) => (float) $variant->harga);
        $gambarProduk = $product?->images
            ->firstWhere('is_primary', true) ?? $product?->images->first();

        $customGambar = $promoConfig?->gambar;
        $finalGambar = $customGambar ?: $voucher->gambar;
        $urlGambar = $customGambar
            ? $this->images->url($customGambar)
            : $this->images->url($voucher->gambar);

        return [
            'id_voucher' => $voucher->id_voucher,
            'kode_voucher' => $voucher->kode_voucher,
            'nama_voucher' => $voucher->nama_voucher,
            'deskripsi' => $voucher->deskripsi,
            'gambar' => $finalGambar,
            'url_gambar' => $urlGambar,
            'badge' => $promoConfig?->badge,
            'tautan_tujuan' => $promoConfig?->tautan_tujuan,
            'urutan_tampilan' => $promoConfig?->urutan_tampilan,
            'tipe_diskon' => $voucher->tipe_diskon,
            'nilai_diskon' => (float) $voucher->nilai_diskon,
            'minimal_pembelian' => (float) $voucher->minimal_pembelian,
            'maksimal_diskon' => $voucher->maksimal_diskon !== null ? (float) $voucher->maksimal_diskon : null,
            'tanggal_mulai' => $voucher->tanggal_mulai,
            'tanggal_selesai' => $voucher->tanggal_selesai,
            'id_product_peruntukan' => $voucher->id_product_peruntukan,
            'nama_produk_peruntukan' => $product?->nama_produk,
            'harga_mulai_peruntukan' => $hargaMulai !== null ? (float) $hargaMulai : null,
            'url_gambar_peruntukan' => $gambarProduk
                ? $this->images->url($gambarProduk->path_gambar)
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hero(): array
    {
        return SlideHero::where('status_aktif', true)
            ->orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->map(fn (SlideHero $slide) => array_merge($slide->toArray(), [
                'url_gambar' => $this->images->url($slide->gambar),
            ]))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kategori(): array
    {
        return CmsKategori::where('status_aktif', true)
            ->orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->map(fn (CmsKategori $item) => array_merge($item->toArray(), [
                'url_gambar' => $this->images->url($item->gambar),
            ]))
            ->all();
    }

    private function galeri(): ?array
    {
        $galeri = CmsGaleri::find(self::SINGLETON_GALERI);

        if (! $galeri || ! $galeri->status_aktif) {
            return null;
        }

        $galeri->load('gambar');

        return [
            'id_galeri' => $galeri->id_galeri,
            'judul' => $galeri->judul,
            'subjudul' => $galeri->subjudul,
            'deskripsi' => $galeri->deskripsi,
            'label_lokasi' => $galeri->label_lokasi,
            'judul_lokasi' => $galeri->judul_lokasi,
            'deskripsi_lokasi' => $galeri->deskripsi_lokasi,
            'gambar' => $galeri->gambar->map(fn (CmsGambarGaleri $item) => array_merge($item->toArray(), [
                'url_gambar' => $this->images->url($item->gambar),
            ]))->values()->all(),
        ];
    }

    private function profil(): ?array
    {
        $profil = ProfilUsaha::find(self::SINGLETON_PROFIL);

        if (! $profil) {
            return null;
        }

        return array_merge($profil->toArray(), [
            'url_logo' => $this->images->url($profil->logo),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fasilitas(): array
    {
        return FasilitasUsaha::where('status_aktif', true)
            ->orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->all();
    }
}
