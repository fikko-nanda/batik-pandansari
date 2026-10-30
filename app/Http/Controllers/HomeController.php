<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Halaman Utama (Homepage)
     * Mengambil data produk pilihan terpisah untuk Section WOMEN'S WEAR dan MEN'S WEAR
     * dengan isolasi kategori yang ketat tanpa pencampuran produk sarimbit/couple.
     *
     * Filtering kategori cukup memakai satu relasi `category` lewat whereHas biasa
     * (satu subquery EXISTS saja, tanpa nested whereExists/whereNotExists berulang)
     * agar query ringan, dan seluruh akses database dibungkus try-catch sehingga
     * halaman tetap tampil dengan koleksi kosong bila layanan database mati.
     */
    public function index(Request $request): View
    {
        $womensProducts = collect();
        $mensProducts = collect();
        $userWishlistIds = [];

        try {
            // 1. Query Section WOMEN'S WEAR (Hanya Busana Wanita, Tanpa Pakaian Pria & Tanpa Sarimbit/Couple)
            $womensProducts = Product::with([
                'category',
                'variants' => fn ($q) => $q->where('status_varian', 'aktif')->where('stok', '>', 0),
                'images' => fn ($q) => $q->where('type', ProductImage::TYPE_PRODUK)->orderByDesc('is_primary')->orderBy('urutan'),
            ])
                ->whereHas('category', function ($query) {
                    $query->where(function ($match) {
                        $match->whereIn('slug', ['pakaian-wanita', 'womens-wear', 'wanita'])
                            ->orWhere('nama_kategori', 'like', '%wanita%');
                    })->where(function ($exclude) {
                        $exclude->whereNotIn('slug', ['sarimbit', 'couple'])
                            ->where('nama_kategori', 'not like', '%sarimbit%')
                            ->where('nama_kategori', 'not like', '%couple%');
                    });
                })
                ->where('status_produk', 'aktif')
                ->latest('dibuat_pada')
                ->take(8)
                ->get();

            // 2. Query Section MEN'S WEAR (Hanya Busana Pria, Tanpa Pakaian Wanita & Tanpa Sarimbit/Couple)
            $mensProducts = Product::with([
                'category',
                'variants' => fn ($q) => $q->where('status_varian', 'aktif')->where('stok', '>', 0),
                'images' => fn ($q) => $q->where('type', ProductImage::TYPE_PRODUK)->orderByDesc('is_primary')->orderBy('urutan'),
            ])
                ->whereHas('category', function ($query) {
                    $query->where(function ($match) {
                        $match->whereIn('slug', ['pakaian-pria', 'mens-wear', 'pria'])
                            ->orWhere('nama_kategori', 'like', '%pria%');
                    })->where(function ($exclude) {
                        $exclude->whereNotIn('slug', ['sarimbit', 'couple'])
                            ->where('nama_kategori', 'not like', '%sarimbit%')
                            ->where('nama_kategori', 'not like', '%couple%');
                    });
                })
                ->where('status_produk', 'aktif')
                ->latest('dibuat_pada')
                ->take(8)
                ->get();

            // Sertakan status boolean `is_liked` bila pengguna sedang login
            $user = auth('sanctum')->user() ?? $request->user();
            if ($user) {
                $userWishlistIds = WishlistItem::where('id_user', $user->id_user)
                    ->pluck('id_product')
                    ->all();
            }
        } catch (\Exception $e) {
            // Koneksi database mati / query gagal: laporkan ke log, jangan crash 500.
            report($e);
        }

        foreach ($womensProducts as $p) {
            $p->setAttribute('is_liked', in_array($p->id_product, $userWishlistIds));
        }
        foreach ($mensProducts as $p) {
            $p->setAttribute('is_liked', in_array($p->id_product, $userWishlistIds));
        }

        return view('home', compact('womensProducts', 'mensProducts'));
    }
}
