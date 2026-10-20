# Dokumentasi API Batik Pandansari

Dokumentasi ini merangkum endpoint yang dilaporkan sudah dibuat di `routes/api.php`. Penjelasan fungsi didasarkan pada daftar endpoint yang diberikan; detail field request, format respons, aturan validasi, dan status HTTP perlu dikonfirmasi langsung dari implementasi Laravel.

## 1. Aturan akses dan role

| Role / kondisi | Hak akses |
|---|---|
| Publik (tanpa login) | Endpoint yang ditandai **Publik**, misalnya registrasi, login, kategori, produk, tes JSON, dan webhook pembayaran. |
| Pengguna login | Endpoint kelompok **Auth (Sanctum)** memerlukan autentikasi. Kelompok ini mencakup profil sendiri, alamat, wishlist, keranjang, voucher tersedia/validasi, ongkir, checkout, dan pesanan milik pengguna. |
| Admin | Endpoint kelompok **Admin** dan **RajaOngkir admin** ditujukan untuk admin. Backend harus memeriksa autentikasi serta role admin; pengguna biasa tidak boleh mengaksesnya. |
| Webhook Midtrans | Dipanggil oleh sistem pembayaran, bukan alur login pelanggan biasa. Verifikasi keaslian notifikasi harus mengikuti implementasi webhook. |

**Catatan:** Daftar ini tidak merinci hak akses setiap endpoint secara terpisah untuk semua role. Gunakan middleware dan policy di kode sebagai sumber kebenaran akhir. Data pribadi harus dibatasi ke pemiliknya atau admin yang berwenang.

## 2. Endpoint publik

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/register` | Mendaftarkan akun pengguna baru. |
| `POST` | `/api/login` | Memeriksa kredensial login dan memberikan hasil autentikasi sesuai mekanisme aplikasi. |
| `GET` | `/api/categories` | Mengambil daftar kategori yang tersedia. |
| `GET` | `/api/categories/{id}` | Mengambil detail kategori berdasarkan ID. |
| `GET` | `/api/products` | Mengambil daftar produk untuk katalog. |
| `GET` | `/api/products/{id}` | Mengambil detail produk berdasarkan ID. |
| `GET` | `/api/products/{id}/variants` | Mengambil varian dari produk tertentu. |
| `GET` | `/api/products/{id}/images` | Mengambil daftar gambar produk tertentu. |
| `POST` | `/api/payments/midtrans/notification` | Menerima notifikasi/webhook Midtrans untuk memproses perubahan status pembayaran. Keaslian notifikasi harus divalidasi. |
| `GET` | `/api/test-json` | Endpoint uji untuk memastikan API dapat mengembalikan respons JSON. |

## 3. Endpoint pengguna login — Sanctum

Endpoint di bagian ini memerlukan autentikasi. Data pribadi seperti profil, alamat, keranjang, wishlist, dan pesanan seharusnya dibatasi pada data milik pengguna yang sedang login, kecuali aturan aplikasi secara eksplisit mengizinkan hal lain.

### 3.1 Profil dan autentikasi

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/logout` | Mengakhiri autentikasi sesuai mekanisme aplikasi. |
| `GET` | `/api/me` | Mengambil informasi akun pengguna yang sedang login. |
| `PUT` | `/api/me` | Memperbarui profil pengguna sendiri. |
| `PUT` | `/api/me/password` | Mengganti kata sandi pengguna yang sedang login. |

### 3.2 Alamat pengguna

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/addresses` | Mengelola alamat pengguna, termasuk tambah, lihat, ubah, dan hapus sesuai route yang terdaftar. |

Daftar yang diberikan menyebutkan lima route alamat, tetapi tidak merinci method dan path lengkap setiap route. Periksa `php artisan route:list --path=api` untuk daftar pastinya.

### 3.3 Wishlist

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/wishlist` | Mengelola daftar produk favorit pengguna. Daftar yang diberikan menyebutkan tiga route; periksa route list untuk method dan path detail. |

### 3.4 Keranjang belanja

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/cart` | Mengambil atau mengelola keranjang belanja pengguna sesuai route yang tersedia. |
| `CRUD` | `/api/cart/items` | Mengelola item keranjang, misalnya menambah, memperbarui jumlah, atau menghapus item sesuai route yang terdaftar. |

Daftar yang diberikan menyebutkan lima route untuk `/api/cart` dan `/api/cart/items` secara gabungan. Method dan path detail perlu diperiksa pada route list.

### 3.5 Voucher

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/vouchers/available` | Mengambil voucher yang tersedia bagi pengguna. |
| `POST` | `/api/vouchers/validate` | Memeriksa apakah voucher dapat digunakan sesuai aturan aplikasi dan data transaksi yang dikirim. |
| `GET` | `/api/voucher-usages/me` | Melihat catatan penggunaan voucher milik pengguna yang sedang login. |

### 3.6 Pengiriman dan checkout

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/shipping/destinations` | Mencari atau mengambil daftar tujuan pengiriman yang disediakan integrasi ongkir. |
| `POST` | `/api/shipping/cost` | Meminta perhitungan biaya pengiriman berdasarkan input seperti tujuan, berat, dan kurir sesuai implementasi. |
| `POST` | `/api/checkout` | Memproses checkout untuk membuat pesanan berdasarkan keranjang dan data yang diperlukan. |

### 3.7 Pesanan dan pembayaran pengguna

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/orders` | Melihat daftar pesanan pengguna yang sedang login. |
| `GET` | `/api/orders/{id}` | Melihat detail pesanan yang boleh diakses pengguna tersebut. |
| `POST` | `/api/orders/{id}/payment` | Memulai atau membuat proses pembayaran untuk pesanan tertentu. |
| `GET` | `/api/orders/{id}/payment` | Mengambil informasi pembayaran untuk pesanan tertentu. |

## 4. Endpoint admin

Semua endpoint di bagian ini ditujukan untuk admin. Backend perlu memvalidasi autentikasi dan role admin, bukan hanya menyembunyikan menu pada frontend.

### 4.1 Manajemen pengguna

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/users` | Mengelola data pengguna oleh admin sesuai operasi route yang tersedia. |
| `CRUD` | `/api/users/{id}` | Melihat atau mengelola pengguna tertentu sesuai method yang terdaftar. |
| `GET` | `/api/admin/users/summary` | Mengambil ringkasan statistik pengguna, seperti total dan pengguna aktif sesuai implementasi. |
| `GET` | `/api/admin/users/export` | Mengunduh ekspor daftar pengguna. Jangan sertakan kata sandi, token, atau rahasia autentikasi. |

Daftar menyebut `CRUD` untuk `/api/users` dan `/api/users/{id}`. Method yang benar-benar terdaftar harus diverifikasi melalui route list.

### 4.2 Manajemen produk, varian, dan gambar

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/products` | Mengelola produk melalui route admin yang tersedia. |
| `CRUD` | `/api/admin/products` | Menyediakan operasi pengelolaan produk admin sesuai route yang terdaftar. |
| `GET` | `/api/admin/products/export` | Mengunduh ekspor daftar produk. |
| `CRUD` | `/api/admin/products/{id}` | Mengakses atau mengelola produk tertentu sesuai method yang terdaftar. |
| `CRUD` | `/api/products/{id}/variants` | Mengelola varian untuk produk tertentu sesuai route yang tersedia. |
| `CRUD` | `/api/variants/{id}` | Mengelola atau mengakses varian tertentu sesuai method yang terdaftar. |
| `CRUD` | `/api/products/{id}/images` | Mengelola gambar milik produk tertentu sesuai route yang tersedia. |
| `CRUD` | `/api/images/{id}` | Mengelola atau mengakses gambar tertentu sesuai method yang terdaftar. |

Daftar awal menggabungkan beberapa operasi dengan label `CRUD`. Periksa method, middleware, dan controller masing-masing untuk memastikan route yang benar-benar tersedia dan hak aksesnya.

### 4.3 Manajemen voucher admin

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/vouchers` | Mengelola data voucher sesuai operasi yang tersedia. |
| `GET` | `/api/vouchers/summary` | Mengambil ringkasan statistik voucher. |
| `POST` | `/api/vouchers/{id}/publish` | Mempublikasikan atau mengaktifkan voucher jika memenuhi aturan aplikasi. |
| `POST` | `/api/vouchers/{id}/unpublish` | Menarik publikasi atau menonaktifkan voucher. |
| `GET` | `/api/voucher-usages` | Melihat data penggunaan voucher untuk keperluan admin sesuai middleware yang diterapkan. |

### 4.4 Manajemen kategori admin

| Method | Endpoint | Fungsi |
|---|---|---|
| `CRUD` | `/api/admin/categories` | Mengelola kategori produk melalui operasi tambah, lihat, ubah, dan hapus yang terdaftar. |

### 4.5 Manajemen pesanan admin

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/admin/orders` | Melihat daftar pesanan untuk pengelolaan admin. |
| `GET` | `/api/admin/orders/{id}` | Melihat detail pesanan tertentu. |
| `GET` | `/api/admin/orders/summary` | Mengambil ringkasan statistik pesanan. |
| `PUT` | `/api/admin/orders/{id}/status` | Memperbarui status pesanan sesuai transisi status yang diizinkan. |
| `POST` | `/api/admin/orders/{id}/fulfillments` | Mencatat tahapan pemenuhan pesanan, misalnya persiapan atau pengemasan. |
| `GET` | `/api/admin/order-fulfillments` | Melihat daftar atau riwayat pemenuhan pesanan. |

Route `/api/admin/orders/summary` sebaiknya didaftarkan sebelum route dinamis `/api/admin/orders/{id}` agar `summary` tidak diperlakukan sebagai ID.

### 4.6 Pembayaran admin

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/admin/payments` | Melihat daftar pembayaran untuk pengelolaan admin. |
| `GET` | `/api/admin/payments/summary` | Mengambil ringkasan statistik pembayaran. |

### 4.7 Pengiriman admin

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/admin/shipments` | Melihat daftar pengiriman, termasuk status dan resi jika tersedia. |
| `PUT` | `/api/admin/shipments/{id}/status` | Memperbarui status pengiriman dan mencatat riwayat perubahan sesuai implementasi. |

### 4.8 Konfigurasi RajaOngkir admin

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/admin/shipping/config` | Membaca konfigurasi pengiriman, seperti kota asal dan status ketersediaan API key tanpa mengungkap nilai rahasianya. |
| `GET` | `/api/admin/shipping/health` | Memeriksa kesiapan konfigurasi dan koneksi layanan pengiriman sesuai implementasi. |
| `GET` | `/api/admin/shipping/couriers` | Melihat daftar kurir yang didukung serta status aktifnya. |
| `PUT` | `/api/admin/shipping/couriers/{code}` | Mengaktifkan atau menonaktifkan kurir tertentu sesuai aturan aplikasi. |
| `POST` | `/api/admin/shipping/simulate` | Menjalankan simulasi perhitungan ongkir. Endpoint ini dapat memanggil API eksternal dan memakai kuota, bergantung implementasinya. |

## 5. Ringkasan role

| Kelompok fitur | Publik | Pengguna login | Admin |
|---|:---:|:---:|:---:|
| Registrasi dan login | Ya | — | — |
| Katalog kategori dan produk | Ya | Ya | Ya, dengan endpoint pengelolaan |
| Profil sendiri, alamat, wishlist, keranjang | Tidak | Ya | Sesuai route dan izin yang diterapkan |
| Voucher tersedia dan validasi | Sesuai route dan aturan aplikasi | Ya | Pengelolaan melalui endpoint admin |
| Checkout dan pesanan milik sendiri | Tidak | Ya | Pengelolaan melalui endpoint admin |
| Ringkasan, ekspor, dan CRUD admin | Tidak | Tidak | Ya |
| Konfigurasi RajaOngkir admin | Tidak | Tidak | Ya |
| Webhook Midtrans | Sistem pembayaran | Tidak memakai alur login pelanggan biasa | Bukan endpoint penggunaan admin biasa |

Tabel ini adalah ringkasan fungsi dari daftar endpoint, bukan pengganti pemeriksaan middleware dan policy di kode.

## 6. Cara memverifikasi route yang benar-benar terdaftar

Jalankan dari direktori backend Laravel:

```powershell
php artisan route:list --path=api
```

Jika project menggunakan PHP Herd Lite pada Windows:

```powershell
& "$env:USERPROFILE\.config\herd-lite\bin\php.exe" artisan route:list --path=api
```

Untuk melihat middleware dengan lebih jelas:

```powershell
& "$env:USERPROFILE\.config\herd-lite\bin\php.exe" artisan route:list --path=api -v
```

Periksa kolom **Method**, **URI**, **Name**, dan **Action**. Dokumentasi ini merangkum daftar yang diberikan dan tidak membuktikan bahwa semua endpoint sudah diuji, semua role sudah dikonfigurasi dengan benar, atau seluruh endpoint sudah terhubung ke frontend.
