# Dokumentasi Frontend — Batik Pandansari

> Panduan struktur komponen Blade dan konvensi untuk **team frontend** dan pegangan untuk **team backend** (data apa yang nantinya harus disediakan).

## 1. Struktur Views

```
resources/views/
├── layouts/                    # 3 layout induk
│   ├── app.blade.php           #   Publik: announcement bar + navbar + footer + WhatsApp FAB (via Vite)
│   ├── admin.blade.php         #   Dashboard admin (Tailwind CDN)
│   └── teknisi.blade.php       #   Engineering console (Tailwind CDN)
│
├── components/                 # ★ SEMUA komponen bersifat anonim (auto-discovery Laravel)
│   ├── announcement-bar.blade.php
│   ├── navbar.blade.php        #   <x-navbar>
│   ├── footer.blade.php        #   <x-footer>
│   ├── whatsapp-button.blade.php  # <x-whatsapp-button> — tombol WA mengambang, global
│   ├── hero.blade.php          #   <x-hero>            — slider hero landing
│   ├── partners.blade.php      #   <x-partners>
│   ├── categories.blade.php    #   <x-categories>
│   ├── womens-wear.blade.php   #   <x-womens-wear>
│   ├── mens-wear.blade.php     #   <x-mens-wear>
│   ├── promo-banner.blade.php  #   <x-promo-banner>
│   ├── craft-categories.blade.php # <x-craft-categories> — 3 Kategori Utama: dinamis dari GET /cms/kategori, fallback statis Tulis/Cap/Handprint
│   ├── gallery.blade.php       #   <x-gallery>
│   ├── consultation-banner.blade.php # <x-consultation-banner>
│   ├── product-card.blade.php  #   <x-product-card> — kartu produk reusable (punya @props)
│   ├── admin/                  #   Komponen khusus modul admin
│   │   ├── sidebar.blade.php   #     <x-admin.sidebar>
│   │   ├── navbar.blade.php    #     <x-admin.navbar>
│   │   └── modal-add-product.blade.php # <x-admin.modal-add-product>
│   └── teknisi/
│       └── sidebar.blade.php   #     <x-teknisi.sidebar>
│
├── landing.blade.php           # Halaman utama — hanya komposisi 9 komponen (lihat §3)
├── shop/                       # katalog, product-detail, cart, checkout
├── customer/                   # profile, orders, address, wishlist, security
├── auth/                       # login, register
├── admin/                      # 11 halaman dashboard admin
└── teknisi/                    # index (dashboard), audit-log, webhook-logs, notification-logs, failed-jobs
```

**Aturan:** Reusable component **wajib** ditaruh di `components/` (boleh subfolder per modul), sehingga bisa dipanggil `<x-...>`. Komponen halaman admin sebelumnya memakai `@include('admin.components...')` — sudah dikonversi ke `<x-admin.*>`.

## 2. Aturan Pemanggilan Komponen

File di `views/components/` **otomatis** terdaftar sebagai atribut Blade (anonymous component):

| Lokasi file                            | Cara panggil                                                                |
| -------------------------------------- | --------------------------------------------------------------------------- |
| `components/navbar.blade.php`          | `<x-navbar />`                                                              |
| `components/admin/sidebar.blade.php`   | `<x-admin.sidebar />`                                                       |
| `components/teknisi/sidebar.blade.php` | `<x-teknisi.sidebar />`                                                     |
| `components/product-card.blade.php`    | `<x-product-card badge="PROMO" title="..." :image="$product->photo_url" />` |

Contoh komponen dengan props (`product-card`):

```blade
<x-product-card
    badge="TERBARU"
    subtitle="SOLO • ATELIER WANITA"
    title="Tunik Batik Cheongsam"
    description="Aksen kerah cheongsam klasik..."
    price="Rp 1.280.000"
    material="Katun Primis Super"
    :image="asset('images/katalog1.webp')"
/>
```

Buat komponen baru via artisan: `php artisan make:component Nama --view` (hanya view, tanpa class).

## 3. Cara Memasang Halaman Baru

1. Pilih layout yang sesuai: publik → `layouts.app`, admin → `layouts.admin`, teknisi → `layouts.teknisi`.
2. Jangan mengedit file layout — cukup isi konten via `@section('content')`:

```blade
@extends('layouts.app')

@section('title', 'Judul Halaman')   {{-- opsional --}}

@section('content')
    ... konten ...
@endsection
```

3. Jika halaman butuh interaktivitas, tulis Alpine.js inline (`x-data`, `x-show`, dll) di dalam bagian `content`.

## 4. Konvensi Frontend

- **Styling:** Tailwind 3 via Vite. Warna brand: `#0F1A24` (teks gelap), `#FAF8F5` (bg), aksen sky. Ditata pada `tailwind.config.js`.
- **Font:** Plus Jakarta Sans (+ Playfair Display di landing) — Google Fonts.
- **CDN vs npm:** FontAwesome & Alpine.js dimuat via CDN. Tailwind **di-Vite-kan** untuk halaman publik. Layout `admin` & `teknisi` masih memakai Tailwind CDN — TODO: migrasi ke Vite agar satu sumber build.
- **Data:** semua halaman utama (publik, customer, admin, teknisi) sudah terhubung API nyata via `PandansariAPI` (lihat §5b). Angka statis hanya tersisa di placeholder banner/galeri.
- **Icon:** FontAwesome 6 (`fa-brands`, `fa-solid`, `fa-regular`).
- Setelah mengubah CSS: `npm run build` (atau `npm run dev` saat developing).
- Validasi syntax Blade: `php artisan view:cache` (lalu `php artisan view:clear`).

## 5. Kontrak untuk Team Backend

Karena frontend sudah dikanoniskan, backend nantinya cukup mengganti data dummy dengan data nyata. Yang perlu backend sediakan:

1. **Jangan mengubah nama route** — frontend memanggil `route('home')`, `route('katalog')`, `route('product.detail', $id)`, `route('customer.*')`, `route('admin.*')`, `route('teknisi.*')`, `route('login')`, `route('register')`.
2. **Jangan mengubah file di `resources/views/components/`** — backend bekerja di `app/`, `routes/`, `database/`.
3. **Model Produk** yang diharapkan frontend (`shop/katalog`, `shop/product-detail`, komponen `product-card`):

    | Field dibutuhkan view | Kolom yang disarankan            | Catatan                                                                     |
    | --------------------- | -------------------------------- | --------------------------------------------------------------------------- |
    | `title`               | `nama_produk`                    | sudah ada                                                                   |
    | `description`         | `deskripsi`                      | sudah ada                                                                   |
    | `price`               | `harga`                          | belum ada — format `Rp 1.280.000` di frontend, kirim angka & format backend |
    | `image`               | `foto` / relasi `product_photos` | belum ada — `asset('images/...')`                                           |
    | badge / label promosi | `status_produk` / tabel promo    | sudah ada enum `draft/aktif/nonaktif`                                       |
    | kategori              | relasi `category`                | sudah ada (`categories.nama`)                                               |
    | stok & material       | `stok`, `material`               | belum ada                                                                   |

4. **Tabel yang belum ada** (dipakai UI dummy): orders, cart_items, addresses, wishlist, payments, promos, settings, audit_log/webhook_log.
5. Layout `admin`/`teknisi` saat ini **tanpa middleware** — backend tinggal add middleware ke route group `admin` & `teknisi` di `routes/web.php`.

## 5b. Status Integrasi API

### ✅ Terpusat di `resources/js/app.js` — Layer `PandansariAPI`

Semua pemanggilan API harus lewat helper ini (Bearer token + envelope `{success, message, data, errors, meta}`). Spesifikasi lengkap backend: [`docs/BACKEND-API.md`](docs/BACKEND-API.md).

### ✅ Badge Keranjang & Wishlist di Navbar (sinkron API)

- Alpine store global `$store.lencana` (`{ cart, wishlist }`) diregistrasi saat `alpine:init` di `app.js`.
- `PandansariAPI.muatLencana()` menarik `GET /cart` + `GET /wishlist` paralel — dipanggil navbar saat halaman dibuka bila user login.
- Semua helper mutasi (`addCartItem`, `updateCartItem`, `removeCartItem`, `clearCart`, `addWishlist`, `removeWishlist`) otomatis menjadwalkan refresh badge (debounce 300 ms) via `jadwalLencana()` — badge selalu sinkron tanpa perlu memodifikasi tiap halaman.
- Badge disembunyikan saat jumlah 0. Logout / token usang → badge di-nol-kan (`clearSession()`).

### ✅ Terhubung

| Modul            | Endpoint                                                                                                                                                                                                                   | File                                                                                                |
| ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Login            | `POST /login` → `data.token` + `data.user`                                                                                                                                                                                 | `auth/login.blade.php`                                                                              |
| Login via Google | `POST /auth/google` _(GIS wajib client ID sama seperti backend)_                                                                                                                                                           | belum — tersedia helper                                                                             |
| Register         | `POST /register`                                                                                                                                                                                                           | `auth/register.blade.php`                                                                           |
| Profil/Keluar    | `GET /me`, `POST /logout`                                                                                                                                                                                                  | `components/navbar.blade.php`                                                                       |
| Katalog          | `GET /categories`, `GET /products?search&category&page&per_page` — menu navbar kirim `?category=<slug>` (tulis/cap/handprint/wanita/pria/anak/sarimbit) → dipetakan ke `id_category` di `shop/katalog.blade.php`           | `shop/katalog.blade.php` + `components/shop/product-item.blade.php`                                 |
| Detail produk    | `GET /products/{id}`, `GET /products/{id}/variants`                                                                                                                                                                        | `shop/product-detail.blade.php`                                                                     |
| Tambah keranjang | `POST /cart/items { id_variant, jumlah }`                                                                                                                                                                                  | `shop/product-detail.blade.php`                                                                     |
| Wishlist         | `POST /wishlist { id_product }`                                                                                                                                                                                            | katalog + detail                                                                                    |
| Keranjang        | `GET /cart`, `PUT/DELETE /cart/items/{id}`, `DELETE /cart`, voucher validate                                                                                                                                               | `shop/cart.blade.php`                                                                               |
| Checkout         | `GET /addresses`, **`GET /vouchers/available` (chips voucher yang bisa dipakai)**, `POST /vouchers/validate`, `POST /checkout` (`is_manual_shipping: true`) → **buka WhatsApp admin** untuk konfirmasi ongkir & pembayaran | `shop/checkout.blade.php`                                                                           |
| Riwayat pesanan  | `GET /orders?page&per_page`, **cek transaksi tertunda `GET /orders/{id}/payment`**, bayar ulang via `POST /orders/{id}/payment`, beli-lagi via `POST /cart/items`                                                          | `customer/orders.blade.php`                                                                         |
| Buku alamat      | CRUD `/addresses` (tambah/ubah/hapus via modal)                                                                                                                                                                            | `customer/address.blade.php`                                                                        |
| Wishlist user    | `GET /wishlist` (relasi `product.category/variants/images`), `DELETE /wishlist/{id}`                                                                                                                                       | `customer/wishlist.blade.php`                                                                       |
| Profil           | `GET /me` (sinkron), `PUT /me { nama_pengguna, email, nomor_telepon }`                                                                                                                                                     | `customer/profile.blade.php`                                                                        |
| Keamanan         | `PUT /me/password { kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation }`                                                                                                                                            | `customer/security.blade.php`                                                                       |
| Voucher saya     | `GET /voucher-usages/me`                                                                                                                                                                                                   | `customer/orders.blade.php` dan kartu **Riwayat Pemakaian Voucher** di `customer/profile.blade.php` |
| Sidebar akun     | `GET /me` + logout di 3 halaman customer                                                                                                                                                                                   | orders/address/wishlist                                                                             |

### ✅ Terhubung — Modul Admin (semua gate: cek token + `peran === 'admin'`)

| Halaman       | Endpoint                                                                                                                                                                                                                                                                                                                                | File                                                                        |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------- | ------------------------------ |
| Dashboard     | `GET /admin/{orders,payments,users}/summary`, list `GET /admin/orders`                                                                                                                                                                                                                                                                  | `admin/dashboard.blade.php`                                                 |
| Produk        | `GET /admin/products` (list+search+filter), `PUT /products/{id}`, `DELETE /products/{id}`, `POST /products` (+ varian + gambar via modal), CSV `/admin/products/export`                                                                                                                                                                 | `admin/products.blade.php` + `components/admin/modal-add-product.blade.php` |
| Stok          | `GET /admin/products` (variants), stepper stok → `PUT /variants/{id}`, toggle toko → `PUT /products/{id}`                                                                                                                                                                                                                               | `admin/stock.blade.php`                                                     |
| Pesanan       | `GET /admin/orders` (filter status), `GET /admin/orders/{id}` (detail), `PUT /admin/orders/{id}/status`, `POST /admin/orders/{id}/fulfillments`, `GET /admin/order-fulfillments`                                                                                                                                                        | `admin/orders.blade.php`                                                    |
| Userlist      | `GET /users` (dinormalisasi helper), `GET /admin/users/summary`, `POST /users`, CSV `/users/export`                                                                                                                                                                                                                                     | `admin/userlist.blade.php`                                                  |
| Promo/Voucher | `GET /vouchers`, `POST /vouchers` (judul 2 jenis: semua produk / `id_product_peruntukan` khusus 1 produk — BE sudah enforce server-side), `GET /vouchers/summary`, `PUT /vouchers/{id}/publish                                                                                                                                          | unpublish`, `DELETE /vouchers/{id}`                                         | `admin/create-promo.blade.php` |
| Midtrans      | `GET /admin/payments` (filter status/provider), `GET /admin/payments/summary`                                                                                                                                                                                                                                                           | `admin/midtrans.blade.php`                                                  |
| Laporan       | `GET /admin/{orders,payments}/summary`, buku kas `GET /admin/payments`                                                                                                                                                                                                                                                                  | `admin/sales-report.blade.php`                                              |
| Pengaturan    | `GET/PUT /me`, `PUT /me/password`, `GET /categories`, `POST/PUT/DELETE /admin/categories`, CRUD `/admin/cms/hero` + `/admin/cms/kategori`, `GET/PUT /admin/cms/galeri` + `POST/PUT/DELETE /admin/cms/galeri/gambar(/{id})`, `GET/PUT /admin/cms/profil-usaha`, CRUD `/admin/cms/fasilitas`, CRUD `/admin/cms/promos` (tab Kelola Promo) | `admin/settings.blade.php`                                                  |

> Kontak banner hero, kategori CMS, galeri atelier, alamat/jam operasional (profil usaha), fasilitas, dan **multi promo beranda** (`GET /cms/promos` + CRUD `/admin/cms/promos`, lihat `docs/ADENDUM-CMS-PROMO-MULTI.md`) sudah terhubung. Landing fallback ke `GET /cms/promo` lalu banner statis. Proxy gambar: `/media/cms-hero|cms-kategori|cms-galeri|cms-promo/{filename}`.

> Tab **Kelola 3 Kategori Utama** tidak lagi meminta link manual. `tautan_tujuan` dihitung otomatis dari slug kategori terpilih (`/katalog?category=<slug>`), lalu disimpan lewat API yang sama. Saat Simpan Semua, link kartu lama yang berbeda ikut diperbaiki. Katalog mendukung slug dari API sekaligus alias lama (`tulis`, `cap`, `handprint`, dst.).
>
> Tab **Kelola Promo Beranda** juga tidak meminta link manual. Link CTA dihitung otomatis: voucher khusus satu produk ke `/detail/{id_product_peruntukan}`, voucher semua produk ke `/katalog`. Nilai ini dikirim saat simpan dan dipakai langsung oleh banner landing, termasuk untuk promo lama.
>
> Tab **Kelola Banner Hero** memakai link otomatis berdasarkan tipe **Kategori**, **Produk**, atau **Katalog**. Kategori berasal dari `GET /categories` (`/katalog?category=<slug>`), produk dari daftar produk aktif `GET /products` (`/detail/<id_product>`), dan Katalog menghasilkan `/katalog`. Tipe/target dipulihkan dari `tautan_tombol` saat halaman dimuat, sehingga tidak perlu kolom backend baru.

### 🔜 Belum (butuh backend)

- Ongkir otomatis saat checkout — `POST /shipping/cost` selalu 422 `RAJAONGKIR_DESTINATION_NOT_FOUND` (bug backend, lihat `docs/BACKEND-API.md` §15). Checkout fallback tarif manual statis. Halaman admin Integrasi RajaOngkir sudah dihapus; endpoint `/admin/shipping/*` tidak dipakai UI.
- CRUD user admin (ubah/hapus) — `GET /users` hanya punya list/detail + `POST /users`.
- Galeri atelier — belum ada endpoint.
- Login Google GIS — menyusul client ID disepakati.
- Sort katalog (`price_asc` dll) — belum didukung API produk.

- **Helper global** ada di `resources/js/app.js` → `PandansariAPI.*`. Semua halaman cukup memanggil ini.
- Token Sanctum disimpan di `localStorage` (kunci: `pandansari_token`) dari `data.token` response login, dikirim via header `Authorization: Bearer`. Profil user (`data.user`, diperbarui `GET /me`) tersimpan di `pandansari_user`.
- **Base URL API** dari `VITE_API_URL` di `.env` (karena URL ngrok free berubah tiap restart tunnel). Fallback default: tunnel ngrok aktif.
- Format respons backend: `{ success, message, data?, errors?, meta? }` — `ok` di helper = HTTP 2xx; pesan error sudah berbahasa Indonesia, langsung ditampilkan di UI.
- Register 2 langkah: `POST /register` (data diri → OTP ke email) → `POST /otp/verify-registration` (`{email, otp}` → token) → kirim-ulang via `POST /otp/resend-registration`.
- Lupa password 3 langkah: `POST /forgot-password` (minta OTP) → `POST /forgot-password/verify-otp` → `POST /forgot-password/reset` (`{email, otp, kata_sandi, kata_sandi_confirmation}`) → kirim-ulang via `POST /forgot-password/resend-otp`.
- Fallback register: bila alur OTP gagal 404, halaman `auth/register` jatuh ke `POST /register` langsung → aktif role `pelanggan` → auto-login. 401 → redirect `/login` otomatis di halaman keranjang/checkout/wishlist.

### Keamanan & sesi panel

- Middleware global `App\Http\Middleware\SecurityHeaders` memasang CSP, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, dan `COOP`; HSTS hanya dikirim saat request HTTPS.
- Vite dev server dipaksa memakai `127.0.0.1:5173` (lihat `vite.config.js`) karena Chrome menolak `http://[::1]:5173` sebagai source CSP. Origin Vite otomatis diizinkan di `script-src`, `style-src`, `font-src`, `img-src`, dan `connect-src` hanya saat environment lokal. Jika `public/hot` tertinggal dari sesi `npm run dev`, CSS/JS dev akan gagal dimuat: hentikan dev server, hapus `public/hot`, lalu `npm run build`.
- Halaman `/admin/*` dan `/teknisi/*` dijaga `panel:admin` / `panel:teknisi` (`EnsurePanelRole`). Cookie `pandansari_panel` bersifat HttpOnly + terenkripsi dan hanya dipasang setelah `POST /session/panel` memverifikasi token ke `GET /api/me` backend.
- Login admin/teknisi memanggil `PandansariAPI.sinkronPanel()` sebelum redirect. Logout panel memakai `PandansariAPI.keluarPanel()`; logout customer juga membersihkan cookie panel lewat `sendBeacon`.
- Di balik ngrok, pastikan `TRUSTED_PROXIES` mencakup loopback (`127.0.0.1,::1`) agar deteksi HTTPS/cookie Secure benar. Untuk PHP Windows yang belum punya CA bundle, isi `MEDIA_PROXY_CA_BUNDLE` (lihat `.env.example`).
- Kartu kategori beranda (`components/categories.blade.php`) mengambil sampel produk pertama lewat `GET /products?category={slug}&per_page=1` untuk `pria`, `wanita`, `anak`, dan `kain`. Karena backend saat ini memfilter dengan `id_category`, slug ramah di-resolve dari `GET /categories`; jika produk pertama belum punya gambar, dicari sampel berikutnya, lalu memakai gambar fallback lokal bila tetap kosong/gagal dimuat.

## 6. Checklist Sebelum Merge

- [ ] `npm run build` sukses
- [ ] Tidak ada `public/hot` tertinggal saat commit/demo (build memakai `public/build`)
- [ ] `php artisan view:cache` compile semua Blade tanpa error
- [ ] Tidak mengubah `routes/web.php` dan `app/`
- [ ] Komponen reusable baru ditaruh di `components/`, bukan inline duplikat
