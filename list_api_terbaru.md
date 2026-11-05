# Daftar API Terbaru Batik Pandansari

Dokumentasi ini merangkum seluruh endpoint yang terdaftar di `routes/api.php`.

> Sumber: `routes/api.php`
> Diperbarui: 31 Oktober 2026
> Prefix Laravel: `/api`

## Informasi Umum

| Item | Keterangan |
|---|---|
| Format | JSON |
| Autentikasi | Laravel Sanctum, gunakan `Authorization: Bearer <token>` |
| Admin | `auth:sanctum` + middleware `admin` |
| Teknisi | `auth:sanctum` + middleware `teknisi` |
| Upload | `multipart/form-data` untuk endpoint yang menerima file |
| Validasi | Umumnya HTTP 422 dengan objek `errors` |

## 1. Publik

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/categories` | Daftar kategori |
| GET | `/api/categories/{id}` | Detail kategori |
| GET | `/api/products` | Daftar produk |
| GET | `/api/products/{id}` | Detail produk |
| GET | `/api/products/{id}/variants` | Daftar varian produk |
| GET | `/api/products/{id}/images` | Daftar gambar produk |
| GET | `/api/cms/homepage` | Data gabungan homepage |
| GET | `/api/cms/promos` | Promo aktif |
| GET | `/api/cms/promo` | Promo tunggal |
| GET | `/api/cms/hero` | Slide hero |
| GET | `/api/cms/kategori` | Kategori highlight |
| GET | `/api/cms/galeri` | Galeri homepage |
| GET | `/api/cms/profil-usaha` | Profil usaha |
| GET | `/api/cms/fasilitas` | Fasilitas usaha |
| GET | `/api/gambar-ukuran` | Panduan ukuran |
| GET | `/api/test-json` | Endpoint uji sederhana |

## 2. Autentikasi dan OTP

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/register` | Registrasi akun |
| POST | `/api/login` | Login email dan password |
| POST | `/api/auth/google` | Login atau registrasi Google |
| GET | `/api/auth/google/config` | Konfigurasi Google Sign-In |
| POST | `/api/otp/verify-registration` | Verifikasi OTP registrasi |
| POST | `/api/otp/resend-registration` | Kirim ulang OTP registrasi |
| POST | `/api/forgot-password` | Meminta OTP reset password |
| POST | `/api/forgot-password/verify-otp` | Verifikasi OTP reset password |
| POST | `/api/forgot-password/reset` | Mengganti password |
| POST | `/api/forgot-password/resend-otp` | Kirim ulang OTP reset password |

## 3. Pelanggan Terautentikasi

Semua endpoint pada bagian ini memakai middleware `auth:sanctum`.

### Akun, Alamat, dan Pengiriman

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/logout` | Logout |
| GET | `/api/me` | Profil pengguna aktif |
| PUT | `/api/me` | Mengubah profil |
| PUT | `/api/me/password` | Mengubah password |
| POST | `/api/me/google` | Menautkan akun Google |
| GET | `/api/addresses` | Daftar alamat |
| POST | `/api/addresses` | Menambah alamat |
| GET | `/api/addresses/{id}` | Detail alamat |
| PUT | `/api/addresses/{id}` | Mengubah alamat |
| DELETE | `/api/addresses/{id}` | Menghapus alamat |
| GET | `/api/shipping/address-search` | Mencari alamat tujuan |
| POST | `/api/shipping/quote` | Menghitung estimasi ongkir |

### Wishlist, Keranjang, Voucher, dan Pesanan

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/wishlist` | Melihat wishlist |
| POST | `/api/wishlist` | Menambah wishlist |
| DELETE | `/api/wishlist/{id}` | Menghapus wishlist |
| GET | `/api/cart` | Melihat keranjang |
| POST | `/api/cart/items` | Menambah item |
| PUT | `/api/cart/items/{id}` | Mengubah jumlah item |
| DELETE | `/api/cart/items/{id}` | Menghapus item |
| DELETE | `/api/cart` | Mengosongkan keranjang |
| GET | `/api/vouchers/available` | Voucher yang tersedia |
| POST | `/api/vouchers/validate` | Validasi voucher |
| GET | `/api/voucher-usages/me` | Penggunaan voucher pelanggan |
| POST | `/api/checkout` | Membuat pesanan |
| GET | `/api/orders` | Riwayat pesanan |
| GET | `/api/orders/{id}` | Detail pesanan |
| GET | `/api/orders/{id}/invoice` | Invoice pesanan |
| POST | `/api/orders/{id}/payment` | Membuat transaksi pembayaran |
| GET | `/api/orders/{id}/payment` | Status pembayaran |

## 4. Admin

Semua endpoint pada bagian ini memakai middleware `auth:sanctum` dan `admin`.

### Produk, Varian, Gambar, dan Kategori

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/admin/products` | Daftar produk admin |
| GET | `/api/admin/products/export` | Export produk |
| GET | `/api/admin/products/{id}` | Detail produk admin |
| POST | `/api/products` | Membuat produk |
| PUT | `/api/products/{id}` | Mengubah produk |
| DELETE | `/api/products/{id}` | Menghapus produk |
| POST | `/api/products/{id}/variants` | Menambah varian |
| POST | `/api/admin/products/{id}/variants/bulk` | Menambah varian massal |
| PUT | `/api/variants/{id}` | Mengubah varian |
| DELETE | `/api/variants/{id}` | Menghapus varian |
| GET | `/api/admin/products/{id}/images` | Daftar gambar produk |
| POST | `/api/products/{id}/images` | Upload gambar produk |
| POST | `/api/admin/products/{id}/images` | Upload gambar melalui jalur admin |
| DELETE | `/api/images/{id}` | Menghapus gambar |
| DELETE | `/api/admin/products/{id}/images/{imageId}` | Menghapus gambar ter-scope |
| POST | `/api/admin/categories` | Membuat kategori |
| PUT | `/api/admin/categories/{id}` | Mengubah kategori |
| DELETE | `/api/admin/categories/{id}` | Menghapus kategori |

### Voucher dan Pengguna

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/vouchers` | Daftar voucher |
| POST | `/api/vouchers` | Membuat voucher |
| GET | `/api/vouchers/summary` | Ringkasan voucher |
| GET | `/api/vouchers/{id}` | Detail voucher |
| PUT | `/api/vouchers/{id}` | Mengubah voucher |
| DELETE | `/api/vouchers/{id}` | Menghapus voucher |
| POST | `/api/vouchers/{id}/publish` | Mengaktifkan voucher |
| POST | `/api/vouchers/{id}/unpublish` | Menonaktifkan voucher |
| DELETE | `/api/vouchers/{id}/gambar` | Menghapus gambar voucher |
| GET | `/api/voucher-usages` | Daftar penggunaan voucher |
| GET | `/api/users` | Daftar pengguna |
| POST | `/api/users` | Membuat pengguna |
| GET | `/api/users/{id}` | Detail pengguna |
| GET | `/api/admin/users/summary` | Ringkasan pengguna |
| GET | `/api/admin/users/export` | Export pengguna |

### Pesanan, Pembayaran, Fulfillment, dan Pengiriman

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/admin/orders` | Daftar pesanan |
| GET | `/api/admin/orders/summary` | Ringkasan pesanan |
| GET | `/api/admin/orders/export` | Export pesanan |
| GET | `/api/admin/orders/{id}` | Detail pesanan |
| PUT | `/api/admin/orders/{id}/status` | Mengubah status pesanan |
| POST | `/api/admin/orders/{id}/fulfillments` | Menambah fulfillment |
| GET | `/api/admin/order-fulfillments` | Daftar fulfillment |
| GET | `/api/admin/payments` | Daftar pembayaran |
| GET | `/api/admin/payments/summary` | Ringkasan pembayaran |
| GET | `/api/admin/payments/export` | Export pembayaran |
| GET | `/api/admin/shipments` | Daftar pengiriman |
| PUT | `/api/admin/shipments/{id}/status` | Mengubah status pengiriman |

### CMS Admin

| Method | Endpoint | Fungsi |
|---|---|---|
| GET / POST / PUT / DELETE | `/api/admin/cms/hero[/{id}]` | Kelola slide hero |
| GET / POST / PUT / DELETE | `/api/admin/cms/kategori[/{id}]` | Kelola kategori CMS |
| GET / PUT | `/api/admin/cms/galeri` | Kelola konfigurasi galeri |
| POST / PUT / DELETE | `/api/admin/cms/galeri/gambar[/{id}]` | Kelola gambar galeri |
| GET / PUT | `/api/admin/cms/profil-usaha` | Kelola profil usaha |
| GET / POST / PUT / DELETE | `/api/admin/cms/fasilitas[/{id}]` | Kelola fasilitas |
| GET / POST | `/api/admin/cms/promos` | Daftar dan membuat promo |
| PUT / DELETE | `/api/admin/cms/promos/{id}` | Mengubah dan menghapus promo |
| GET / PUT / POST | `/api/admin/cms/promo` | Kelola konfigurasi promo tunggal |
| DELETE | `/api/admin/cms/promo/gambar` | Menghapus gambar promo |
| GET / POST / PUT / DELETE | `/api/admin/gambar-ukuran[/{id}]` | Kelola panduan ukuran |

### Dashboard, Laporan, Inventaris, dan Stock Opname

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/admin/dashboard/summary` | Ringkasan dashboard |
| GET | `/api/admin/dashboard/revenue` | Pendapatan |
| GET | `/api/admin/dashboard/orders` | Data pesanan dashboard |
| GET | `/api/admin/dashboard/aov` | Average order value |
| GET | `/api/admin/reports/summary` | Ringkasan laporan |
| GET | `/api/admin/reports/transactions` | Daftar transaksi |
| GET | `/api/admin/reports/transactions/{id}` | Detail transaksi |
| GET | `/api/admin/reports/export/pdf` | Export laporan PDF |
| GET | `/api/admin/inventory` | Daftar stok |
| GET | `/api/admin/inventory/{variant_id}` | Detail stok varian |
| GET | `/api/admin/inventory/movements` | Pergerakan stok |
| POST | `/api/admin/inventory/adjustments` | Penyesuaian stok |
| GET | `/api/admin/inventory/low-stock` | Stok menipis |
| GET | `/api/admin/inventory/summary` | Ringkasan inventaris |
| GET | `/api/admin/inventory/valuation` | Valuasi inventaris |
| GET | `/api/admin/inventory/export` | Export inventaris |
| GET / POST | `/api/admin/stock-opnames` | Daftar dan membuat stock opname |
| GET | `/api/admin/stock-opnames/{id}` | Detail stock opname |
| POST | `/api/admin/stock-opnames/{id}/items` | Menambah hasil hitung fisik |
| POST | `/api/admin/stock-opnames/{id}/complete` | Menyelesaikan stock opname |

### Zona Tarif Pengiriman

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/admin/shipping/zones` | Daftar zona tarif |
| POST | `/api/admin/shipping/zones` | Membuat zona tarif |
| GET | `/api/admin/shipping/zones/{id}` | Detail zona tarif |
| PUT | `/api/admin/shipping/zones/{id}` | Mengubah zona atau status aktif |

## 5. Teknisi

Semua endpoint memakai prefix `/api/teknisi`, `auth:sanctum`, dan `teknisi`.

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/teknisi/dashboard` | Dashboard monitoring |
| GET | `/api/teknisi/audit-logs` | Daftar audit log |
| GET | `/api/teknisi/audit-logs/{id}` | Detail audit log |
| GET | `/api/teknisi/webhook-logs` | Daftar webhook log |
| GET | `/api/teknisi/webhook-logs/{id}` | Detail webhook log |
| GET | `/api/teknisi/notification-logs` | Daftar notification log |
| GET | `/api/teknisi/notification-logs/{id}` | Detail notification log |
| GET | `/api/teknisi/health` | Health check aplikasi |
| GET | `/api/teknisi/failed-jobs` | Daftar job gagal |

## 6. Webhook Midtrans

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/payments/midtrans/notification` | Menerima notifikasi pembayaran |
| POST | `/api/midtrans/notification` | Alias webhook Midtrans |

Webhook dipanggil Midtrans tanpa login pengguna, tetapi request diverifikasi melalui signature dan rate limit.

## 7. API yang Dipakai Halaman Shipping

Referensi: [shipping.blade.php](resources/views/admin/shipping.blade.php).

| Method | Endpoint | Pemakaian |
|---|---|---|
| GET | `/api/admin/cms/profil-usaha` | Mengambil alamat dan koordinat asal toko |
| PUT | `/api/admin/cms/profil-usaha` | Menyimpan alamat, latitude, dan longitude |
| GET | `/api/admin/shipping/zones` | Mengambil daftar zona tarif |
| POST | `/api/admin/shipping/zones` | Membuat zona tarif |
| PUT | `/api/admin/shipping/zones/{id}` | Mengubah zona atau status aktif |

Pencarian alamat toko menggunakan fungsi frontend `PandansariAPI.searchOsmAddress(...)` yang mengakses layanan OpenStreetMap/Nominatim eksternal, bukan route internal Laravel.

## Catatan

- Dokumentasi ini mengikuti deklarasi pada `routes/api.php`, bukan route halaman pada `routes/web.php`.
- Placeholder seperti `{id}`, `{imageId}`, dan `{variant_id}` harus diganti dengan nilai sebenarnya.
- Query, pagination, dan payload mengikuti validasi controller terkait.
- Validasi route secara langsung membutuhkan PHP 8.4 sesuai requirement proyek.
