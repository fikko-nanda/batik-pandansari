<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsGaleri;
use App\Models\CmsGambarGaleri;
use App\Models\CmsKategori;
use App\Models\FasilitasUsaha;
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
 * GET /api/cms/homepage   agregat seluruh konten beranda
 *
 * Banner Promo memakai tabel `vouchers` (kolom gambar + nama_voucher +
 * deskripsi), bukan tabel terpisah. Voucher yang dipilih adalah voucher aktif
 * yang sedang berjalan; bila ada beberapa, diprioritaskan yang memiliki gambar
 * lalu yang paling baru dibuat.
 */
class CmsHomepageController extends Controller
{
    private const SINGLETON_GALERI = 1;

    private const SINGLETON_PROFIL = 1;

    public function __construct(private readonly ImageUploadService $images) {}

    public function promo(): JsonResponse
    {
        $voucher = $this->featuredVoucher();

        return response()->json([
            'success' => true,
            'message' => $voucher ? 'Promo berhasil diambil.' : 'Belum ada promo aktif.',
            'data' => $voucher ? $this->presentVoucher($voucher) : null,
        ]);
    }

    public function homepage(): JsonResponse
    {
        $voucher = $this->featuredVoucher();

        return response()->json([
            'success' => true,
            'message' => 'Konten halaman utama berhasil diambil.',
            'data' => [
                'hero' => $this->hero(),
                'kategori' => $this->kategori(),
                'promo' => $voucher ? $this->presentVoucher($voucher) : null,
                'galeri' => $this->galeri(),
                'profil_usaha' => $this->profil(),
                'fasilitas' => $this->fasilitas(),
            ],
        ]);
    }

    private function featuredVoucher(): ?Voucher
    {
        $now = Carbon::now();

        return Voucher::query()
            ->where('status_voucher', 'aktif')
            ->where('tanggal_mulai', '<=', $now)
            ->where('tanggal_selesai', '>=', $now)
            ->orderByRaw('CASE WHEN gambar IS NULL OR gambar = ? THEN 1 ELSE 0 END', [''])
            ->orderByDesc('dibuat_pada')
            ->first();
    }

    private function presentVoucher(Voucher $voucher): array
    {
        return [
            'id_voucher' => $voucher->id_voucher,
            'kode_voucher' => $voucher->kode_voucher,
            'nama_voucher' => $voucher->nama_voucher,
            'deskripsi' => $voucher->deskripsi,
            'gambar' => $voucher->gambar,
            'url_gambar' => $this->images->url($voucher->gambar),
            'tipe_diskon' => $voucher->tipe_diskon,
            'nilai_diskon' => (float) $voucher->nilai_diskon,
            'minimal_pembelian' => (float) $voucher->minimal_pembelian,
            'maksimal_diskon' => $voucher->maksimal_diskon !== null ? (float) $voucher->maksimal_diskon : null,
            'tanggal_mulai' => $voucher->tanggal_mulai,
            'tanggal_selesai' => $voucher->tanggal_selesai,
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
