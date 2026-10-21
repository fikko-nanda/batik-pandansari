# Backend Batik Pandansari — Referensi API

Dokumen ini merangkum seluruh endpoint yang sudah ada di folder ini (sumber: `routes/api.php`),
berikut contoh request dan cara mengujinya dengan Thunder Client.

## Info umum

- Base URL: `http://localhost:8000/api`
- Header wajib: `Accept: application/json`
- Auth: `Authorization: Bearer <token>` (Laravel Sanctum)
- Format sukses: `{ "success": true, "message": "...", "data": ... }`
- Validasi gagal `422`: `{ "success": false, "message": "...", "errors": {...} }`
- Belum login `401`: `{ "success": false, "message": "Tidak terautentikasi..." }`
- Bukan admin `403`: `{ "success": false, "message": "Akses ditolak. Endpoint ini hanya untuk admin." }`
- Role di DB: `pelanggan`, `admin` (register publik selalu `pelanggan`).
- Akun admin uji: `admin@example.com` / `admin12345`

## Daftar ringkas endpoint

| # | Method | Endpoint | Akses |
|---|--------|----------|-------|
| 1 | POST | `/register` | Publik |
| 2 | POST | `/login` | Publik |
| 3 | POST | `/logout` | Login |
| 4 | GET | `/me` | Login |
| 5 | GET | `/user` | Admin |
| 6 | GET | `/test-json` | Publik |
| 7 | GET | `/categories` | Publik |
| 8 | GET | `/categories/{id}` | Publik |
| 9 | GET | `/products` | Publik |
| 10 | GET | `/products/{id}` | Publik |
| 11 | POST | `/products` | Admin |
| 12 | PUT | `/products/{id}` | Admin |
| 13 | DELETE | `/products/{id}` | Admin |
| 14 | GET | `/products/{id}/variants` | Publik |
| 15 | POST | `/products/{id}/variants` | Admin |
| 16 | PUT | `/variants/{id}` | Admin |
| 17 | DELETE | `/variants/{id}` | Admin |
| 18 | GET | `/products/{id}/images` | Publik |
| 19 | POST | `/products/{id}/images` | Admin |
| 20 | DELETE | `/images/{id}` | Admin |
| 20a | POST | `/admin/products/{id}/variants/bulk` | Admin |
| 20b | GET | `/admin/products/{id}/images` | Admin |
| 20c | POST | `/admin/products/{id}/images` | Admin |
| 20d | DELETE | `/admin/products/{id}/images/{imageId}` | Admin |
| 21 | GET | `/addresses` | Login (milik sendiri) |
| 22 | POST | `/addresses` | Login (milik sendiri) |
| 23 | GET | `/addresses/{id}` | Login (milik sendiri) |
| 24 | PUT | `/addresses/{id}` | Login (milik sendiri) |
| 25 | DELETE | `/addresses/{id}` | Login (milik sendiri) |
| 26 | POST | `/checkout` | Login |
| 27 | GET | `/orders` | Login (milik sendiri) |
| 28 | GET | `/orders/{id}` | Login (milik sendiri) |
| 29 | POST | `/orders/{id}/payment` | Login (milik sendiri) |
| 30 | GET | `/orders/{id}/payment` | Login (milik sendiri) |
| 31 | GET | `/admin/orders` | Admin |
| 32 | GET | `/admin/orders/{id}` | Admin |
| 33 | PUT | `/admin/orders/{id}/status` | Admin |
| 34 | GET | `/admin/payments` | Admin |
| 35 | POST | `/payments/midtrans/notification` | Publik (webhook) |
| 36 | GET | `/shipping/destinations` | Login |
| 37 | POST | `/shipping/cost` | Login |
| 38 | PUT | `/me` | Login |
| 39 | PUT | `/me/password` | Login |
| 40 | POST | `/vouchers/{id}/publish` | Admin |
| 41 | POST | `/vouchers/{id}/unpublish` | Admin |
| 42 | GET | `/vouchers/summary` | Admin |
| 43 | GET | `/admin/orders/summary` | Admin |
| 44 | GET | `/admin/payments/summary` | Admin |
| 45 | GET | `/admin/shipments` | Admin |
| 46 | PUT | `/admin/shipments/{id}/status` | Admin |
| 47 | GET | `/admin/order-fulfillments` | Admin |
| 48 | POST | `/admin/orders/{id}/fulfillments` | Admin |
| 49 | GET | `/admin/shipping/config` | Admin |
| 50 | PUT | `/admin/shipping/config` | Admin |
| 51 | GET | `/admin/shipping/health` | Admin |
| 52 | GET | `/admin/shipping/couriers` | Admin |
| 53 | PUT | `/admin/shipping/couriers/{code}` | Admin |
| 54 | POST | `/admin/shipping/simulate` | Admin |
| 55 | GET | `/admin/products` | Admin |
| 56 | GET | `/admin/products/{id}` | Admin |
| 57 | GET | `/admin/products/export` | Admin |
| 58 | POST | `/users` | Admin |
| 59 | GET | `/users/{id}` | Admin |
| 60 | GET | `/admin/users/summary` | Admin |
| 61 | GET | `/admin/users/export` | Admin |
| 62 | POST | `/admin/categories` | Admin |
| 63 | PUT | `/admin/categories/{id}` | Admin |
| 64 | DELETE | `/admin/categories/{id}` | Admin |

> Total 81 route (termasuk route sebelumnya yang tidak ditampilkan di tabel ringkas ini — daftar lengkap: `php artisan route:list --path=api`).

---

## 1–2. Autentikasi

### POST `/register` (publik)

Body JSON:

```json
{
  "nama_pengguna": "Budi Santoso",
  "email": "budi@example.com",
  "nomor_telepon": "08123456789",
  "kata_sandi": "rahasia123",
  "kata_sandi_confirmation": "rahasia123"
}
```

- Wajib: `nama_pengguna` (maks 100), `email` (format email, unik, maks 150), `kata_sandi` (min 8 + konfirmasi).
- Opsional: `nomor_telepon` (maks 20).
- Respons `201`: data pengguna (peran otomatis `pelanggan`, tanpa token).
- Email sudah ada → `422`.

### POST `/login` (publik)

```json
{ "email": "admin@example.com", "kata_sandi": "admin12345" }
```

Respons `200`:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": { "token": "1|xxxx", "token_type": "Bearer", "user": {} }
}
```

Kredensial salah → `401`.

## 3–6. Sesi, profil, dan cek server

- POST `/logout` (login) → hapus token aktif, `200`.
- GET `/me` (login) → profil pengguna login.
- PUT `/me` (login) → perbarui profil sendiri. Body parsial: `nama_pengguna` (bila dikirim: wajib, 2–100), `email` (format valid, unik), `nomor_telepon` (maks 20). Contoh: `{ "nama_pengguna": "Pelanggan Baru" }` → `200` + objek user; email kepemilikan orang lain → `422` (`The email has already been taken.`); tanpa token → `401`.
- PUT `/me/password` (login) → ganti kata sandi. Body: `kata_sandi_saat_ini` (sandi lama), `kata_sandi` (min 8), `kata_sandi_confirmation`. Sandi lama salah → `422` `"Kata sandi saat ini salah."` (data tidak diubah); sukses → `200` `{ "success": true, "message": "Kata sandi berhasil diperbarui.", "data": { "email": "..." } }`.
- GET `/user` (admin) → sama seperti `/me`, tetapi hanya bisa diakses admin.
- GET `/test-json` (publik) → `"TEST LARAVEL BERHASIL"` (cek server hidup).

---

## Manajemen pengguna (admin)

### POST `/users` (admin)

```json
{
  "nama_pengguna": "Pengguna Baru",
  "email": "pengguna.baru@example.com",
  "nomor_telepon": "081234567890",
  "kata_sandi": "rahasia123",
  "kata_sandi_confirmation": "rahasia123",
  "peran": "pelanggan"
}
```

- Wajib: `nama_pengguna` (maks 80), `email` (unik), `kata_sandi` (min 8 + konfirmasi).
- Opsional: `nomor_telepon` (maks 20), `peran` (`pelanggan`/`admin`, default `pelanggan`).
- Respons `201` `"Pengguna berhasil dibuat."`; `data` tanpa `kata_sandi`/`remember_token`/token. Sandi tersimpan ter-hash.
- Email duplikat → `422`; non-admin → `403`; tanpa token → `401`.

### GET `/users/{id}` (admin)

Detail pengguna. Tidak ada → `404` `"Pengguna tidak ditemukan."`; `data` tanpa kredensial.

### GET `/admin/users/summary` (admin)

```json
{
  "success": true,
  "message": "Ringkasan pengguna berhasil diambil.",
  "data": {
    "total_pengguna": 18,
    "pengguna_aktif": 18,
    "per_peran": { "pelanggan": 16, "admin": 2 }
  }
}
```

Tabel `users` tanpa kolom status → `pengguna_aktif` = `total_pengguna`.

### GET `/admin/users/export` (admin)

Unduhan CSV (`text/csv; charset=UTF-8`, `attachment; filename=pengguna-YYYY-MM-DD.csv`).
Kolom: `id_user, nama_pengguna, email, nomor_telepon, peran, dibuat_pada`. Tanpa paginasi; tanpa kredensial/token.

---

## 7–8. Kategori

### GET `/categories` (publik)

Daftar kategori. Data uji: `Batik Tulis`, `Batik Cap`, `Batik Modern`.

### GET `/categories/{id}` (publik)

`{id}` = `id_category`. Tidak ada → `404`.

### POST `/admin/categories` (admin)

```json
{
  "nama_kategori": "Batik Cap",
  "deskripsi": "Kategori batik cap Pekalongan",
  "lokasi_gambar": "categories/batik-cap.jpg",
  "status_aktif": true
}
```

- Wajib: `nama_kategori` (maks 80, **tidak** unik). Opsional: `slug` (unik, maks 100; auto-slugify & dibuat unik bila kosong), `deskripsi`, `lokasi_gambar` (maks 200), `status_aktif` (default `true`).
- Respons `201` `"Kategori berhasil dibuat."`.

### PUT `/admin/categories/{id}` (admin)

Parsial (`sometimes`); `slug` unik mengecualikan dirinya. `200` `"Kategori berhasil diperbarui."`; `404` bila tak ada.

### DELETE `/admin/categories/{id}` (admin)

**Nonaktifkan**, bukan hapus fisik: `status_aktif=false` (baris tetap ada; relasi produk aman karena FK `ON DELETE RESTRICT`). `200` `"Kategori berhasil dinonaktifkan."` (idempoten); `404` bila tak ada.

---

## 9–13. Produk

### GET `/products` (publik)

Query opsional: `search` (nama/deskripsi), `category` (id_category), `page`, `per_page` (default 10, maks 100).
Hanya produk `aktif`. Respons menyertakan `meta` (current_page, per_page, total, last_page).

Contoh: `/products?search=parang`, `/products?category=9f5d8990-c871-11f1-8a96-002b6777bbb3&page=1`

### GET `/products/{id}` (publik)

`{id}` = `id_product`. Menyertakan `category`, `variants` (hanya `aktif`), `images`. Non-aktif/tidak ada → `404`.

Data uji: `3b7676c0-c898-11f1-8b52-002b6777bbb3` (Batik Parang Klasik).

### POST `/products` (admin)

```json
{
  "id_category": "9f5d8990-c871-11f1-8a96-002b6777bbb3",
  "nama_produk": "Batik Test",
  "slug": "batik-test",
  "deskripsi": "Contoh",
  "status_produk": "aktif"
}
```

- Wajib: `id_category` (harus ada), `nama_produk` (maks 150).
- Opsional: `slug` (unik, maks 180; auto dari nama bila kosong), `deskripsi`, `sku` (unik, maks 100), `berat_produk` (gram, 1..1000000), `bahan_kain` (maks 100), `status_produk` (`draft/aktif/nonaktif`, default `draft`).
- Respons `201`.
- `status_produk='aktif'` hanya bisa dipakai bila produk sudah punya minimal 1 foto produk (`type='produk'`). Buat sebagai `draft`, unggah gambarnya, lalu aktifkan lewat `PUT`.

### PUT `/products/{id}` (admin)

Parsial (semua opsional). `slug` dan `sku` unik kecuali dirinya. `404` bila tak ada.
Field yang tidak dikirim tetap; field yang dikirim `null` ditulis `null` (kecuali `slug`).
Perpindahan status ke `aktif` mensyaratkan produk sudah punya minimal 1 foto produk.

### DELETE `/products/{id}` (admin)

Soft delete → `status_produk='nonaktif'`. `404` bila tak ada.

### GET `/admin/products` (admin)

Semua produk (termasuk `draft`/`nonaktif`) + relasi `variants`. Query publik (`search`, `category`, `page`, `per_page`) ditambah `status` (`draft/aktif/nonaktif`), `sort` (`nama_produk`/`dibuat_pada`/`status_produk`, default `dibuat_pada`), dan `order` (`asc`/`desc`, default `asc`). Parameter khusus admin hanya divalidasi di jalur admin; `GET /products` publik tidak berubah (tanpa `variants`, parameter tsb diabaikan).

### GET `/admin/products/{id}` (admin)

Detail produk dengan semua varian (termasuk nonaktif). `404` bila tak ada.

### GET `/admin/products/export` (admin)

Unduhan CSV (`text/csv; charset=UTF-8`, `attachment; filename=produk-YYYY-MM-DD.csv`).
Kolom: `id_product, nama_produk, slug, kategori, status_produk, harga_min, harga_maks, stok_total, jumlah_varian, dibuat_pada`.
Harga/stok = agregat `product_variants` (`MIN`/`MAX` harga, `SUM` stok, `COUNT` varian). Menerima filter/urutan yang sama dengan daftar admin; tanpa paginasi. Rute `export` didaftarkan sebelum `/{id}` agar tidak tertangkap wildcard.

---

## 14–17. Varian produk

### GET `/products/{id}/variants` (publik)

`{id}` = `id_product`; hanya produk `aktif` dan varian `status_varian='aktif'`. Tak ada → `404`.

### POST `/products/{id}/variants` (admin)

```json
{
  "sku": "PARANG-M-2",
  "nama_varian": "Parang M",
  "warna": "Cokelat",
  "ukuran": "M",
  "harga": 175000,
  "stok": 10,
  "status_varian": "aktif"
}
```

- Wajib: `sku` (unik, maks 100), `harga` (numerik ≥ 0).
- Opsional: `nama_varian` (≤100), `warna` (≤50), `ukuran` (≤30), `stok` (integer ≥0, default 0), `status_varian` (`aktif/nonaktif`, default `aktif`).
- `201`. SKU duplikat/harga negatif → `422`.

### PUT `/variants/{id}` (admin)

`{id}` = `id_variant`. Parsial; `sku` unik kecuali dirinya. `404` bila tak ada.

### DELETE `/variants/{id}` (admin)

Soft delete → `status_varian='nonaktif'`. `404` bila tak ada.
Ditolak `422` bila varian tersebut satu-satunya varian `aktif` yang memiliki ukuran pada produk itu (produk tanpa varian boleh tetap ada).

### POST `/admin/products/{id}/variants/bulk` (admin)

Simpan banyak varian sekaligus (maks 500 item).

```json
{
  "items": [
    { "sku": "PARANG-M-3", "warna": "Cokelat", "ukuran": "M", "harga": 175000, "stok": 10 },
    { "id_variant": "3b7c252a-c898-11f1-8b52-002b6777bbb3", "stok": 15 }
  ],
  "hapus_yang_tidak_disediakan": false
}
```

- `items` wajib (1..500). `items.*.sku` wajib & unik; `items.*.harga` wajib (≥0).
- Item tanpa `id_variant` = varian baru; item dengan `id_variant` (harus milik produk ini) diperbarui secara parsial.
- SKU duplikat dalam payload atau bentrok dengan varian lain → `422`; varian yang diperbarui boleh tetap memakai SKU-nya sendiri.
- `hapus_yang_tidak_disediakan=true` menonaktifkan varian aktif yang tidak ada di payload.
- Respons `200`: `dibuat`, `diperbarui`, `dinonaktifkan`, `items`.

Data uji varian: `3b7c252a-c898-11f1-8b52-002b6777bbb3` (SKU `PARANG-3b7c252a`).

---

## 18–20. Gambar produk

### GET `/products/{id}/images` (publik)

`{id}` = `id_product`; hanya produk `aktif`. Urut `is_primary` desc, `urutan` asc. Tiap item punya `url`.
Query opsional `?type=produk|size_chart`.

### POST `/products/{id}/images` (admin) — `multipart/form-data`

| Field | Tipe | Wajib |
|-------|------|-------|
| `gambar` | file jpg/jpeg/png/webp, maks 2MB | ya |
| `type` | `produk` / `size_chart` (default `produk`) | tidak |
| `id_variant` | id varian milik produk ini; wajib kosong untuk `size_chart` | tidak |
| `alt_text` | teks ≤150 | tidak |
| `is_primary` | boolean | tidak |
| `urutan` | integer ≥0 | tidak |

File disimpan ke disk `public` folder `products/`; `path_gambar` = path relatif.
Gambar pertama otomatis primary; `is_primary=true` menonaktifkan primary lain.
Respons `201` + `url` (mis. `http://localhost:8000/storage/products/xxx.png`).

Batas: maksimal 5 foto produk (`type='produk'`) per produk dan maksimal 1 size chart. Size chart tidak memakai `id_variant`.

### DELETE `/images/{id}` (admin)

`{id}` = `id_image`. Hapus baris + file fisik. `404` bila tak ada.
Bila gambar utama terhapus, gambar berikutnya otomatis dipromosikan menjadi utama.

### GET `/admin/products/{id}/images` (admin)

Sama seperti daftar gambar publik, namun produk `draft`/`nonaktif` juga terlihat. Query `?type=produk|size_chart` tersedia.

### POST `/admin/products/{id}/images` (admin)

Sama seperti unggah gambar di atas; dipakai untuk produk yang belum `aktif`.

### DELETE `/admin/products/{id}/images/{imageId}` (admin)

Hapus gambar yang scoping-nya ke produk tersebut. `{imageId}` = `id_image`; bila gambar bukan milik produk pada path tersebut → `404`. Hapus baris + file fisik, dan promote gambar utama berikutnya bila perlu.

Data uji gambar: `3b844d0c-c898-11f1-8b52-002b6777bbb3`.

---

## 21–25. Alamat pengguna (butuh login, hanya milik sendiri)

Field (semua wajib kecuali `alamat_utama`):

| Field | Keterangan |
|-------|------------|
| `label_alamat` | maks 30 |
| `nama_penerima` | maks 100 |
| `nomor_telepon` | maks 20 |
| `nama_provinsi` | maks 60 |
| `nama_kota` | maks 60 |
| `nama_kecamatan` | maks 60 |
| `kode_pos` | maks 10 |
| `alamat_lengkap` | teks |
| `alamat_utama` | boolean, opsional |

Aturan: alamat pertama otomatis utama; set `alamat_utama=true` menonaktifkan utama lain
(hanya satu utama per pengguna). Alamat milik user lain → `404`.

- GET `/addresses` → daftar milik sendiri (urut utama lalu terbaru).
- POST `/addresses` → contoh body:

```json
{
  "label_alamat": "Rumah",
  "nama_penerima": "Budi Santoso",
  "nomor_telepon": "08123456789",
  "nama_provinsi": "Jawa Tengah",
  "nama_kota": "Surakarta",
  "nama_kecamatan": "Banjarsari",
  "kode_pos": "57134",
  "alamat_lengkap": "Jl. Contoh No. 10",
  "alamat_utama": true
}
```

- GET `/addresses/{id}` → `{id}` = `id_user_address`.
- PUT `/addresses/{id}` → parsial.
- DELETE `/addresses/{id}` → `200`.

> Catatan: PK aktual adalah `id_user_address` (dokumen lama menulis `id_address`).

---

## Checkout, Pesanan, & Pembayaran (Midtrans Snap)

Alur status pesanan: `menunggu_pembayaran` → `dibayar` → `diproses` → `dikirim` → `selesai`, plus `dibatalkan`.

### POST `/checkout` (login)

Membuat pesanan dari keranjang `aktif`. Subtotal dari harga varian saat checkout, stok langsung berkurang, keranjang ditandai `checkout`, riwayat status awal `menunggu_pembayaran` dibuat.

```json
{
  "id_user_address": "660e8400-e29b-41d4-a716-446655440000",
  "kurir": "jne",
  "layanan": "CTC",
  "kode_voucher": "BATIKHEMAT",
  "biaya_pengiriman": 18000,
  "catatan": "Tolong dikemas rapi"
}
```

- Wajib: `id_user_address` (harus alamat milik sendiri), `kurir` (`jne`/`pos`/`tiki`/`sicepat`/`jnt`/`anteraja`/`jxpress`/`lex`/`lion`/`ninja`), `layanan` (kode layanan dari `POST /shipping/cost`). Opsional: `kode_voucher`, `catatan`.
- `biaya_pengiriman` **diabaikan** (dipertahankan hanya untuk kompatibilitas klien) — ongkir dihitung ulang backend dari RajaOngkir.
- Berat total = Σ(`berat_gram` varian × jumlah); tujuan = `id_tujuan_rajaongkir` pada alamat.
- `404` bila alamat bukan milik user; `422` bila keranjang kosong, stok kurang, varian/produk nonaktif, voucher tidak valid, alamat tanpa `id_tujuan_rajaongkir` (`ALAMAT_TANPA_TUJUAN_RAJAONGKIR`), layanan tidak tersedia (`LAYANAN_TIDAK_TERSEDIA`), atau RajaOngkir menolak request.
- `502` bila upstream RajaOngkir bermasalah; `503` bila koneksi RajaOngkir gagal — pada semua kegagalan ongkir pesanan **tidak** dibuat.
- Respons `201`: objek pesanan + `items`.

### Pesanan

- GET `/orders` (login) → pesanan milik sendiri, terbaru dulu. Query: `status`, `page`, `per_page` (default 10).
- GET `/orders/{id}` (login, pemilik) → detail + item, pembayaran, voucher, riwayat status. Bukan milik sendiri → `404`.
- GET `/admin/orders` (admin) → semua pesanan. Query: `status`, `search`, `dari`, `sampai`, `page`, `per_page` (default 15).
- GET `/admin/orders/summary` (admin) → rekap pesanan. Query: `status`, `dari`, `sampai`. Contoh respons aktual: `{ "total_pesanan": 3, "total_nilai": 400000, "rata_rata_nilai": 133333.33, "per_status": { "menunggu_pembayaran": 1, "dibayar": 1, "diproses": 0, "dikirim": 0, "selesai": 0, "dibatalkan": 1 } }` (didaftarkan **sebelum** `/admin/orders/{id}` agar tertangkap lebih dulu).
- GET `/admin/orders/{id}` (admin) → detail pesanan.
- PUT `/admin/orders/{id}/status` (admin):

```json
{ "status_pesanan": "diproses", "catatan": "Pesanan sedang disiapkan" }
```

`status_pesanan`: `menunggu_pembayaran` / `dibayar` / `diproses` / `dikirim` / `selesai` / `dibatalkan`.
Ke `dibatalkan` (dari `menunggu_pembayaran`/`dibayar`) → stok dikembalikan, penggunaan voucher dibatalkan. Ke `dibayar` → voucher `menunggu` menjadi `digunakan`.

### Ongkos Kirim (RajaOngkir)

Konfigurasi `.env`:

```
RAJAONGKIR_API_KEY=
RAJAONGKIR_BASE_URL=https://rajaongkir.komerce.id/api/v1
RAJAONGKIR_ORIGIN_ID=
# Kosongkan agar CA otomatis terdeteksi (php.ini atau cacert bawaan SDK Midtrans)
RAJAONGKIR_VERIFY=
```

- GET `/shipping/destinations?search={kelurahan}` (login) → daftar tujuan (per kelurahan) dari RajaOngkir; hasil di-cache 7 hari agar hemat kuota. `422` bila `search` kosong/kurang dari 2 karakter.
- POST `/shipping/cost` (login) body `{ destination, weight, courier }` → daftar layanan/kurir beserta `cost` & `etd`. `422` untuk parameter salah/tujuan tidak dikenal/tanpa layanan, `502` upstream bermasalah, `503` koneksi gagal.

Ketahanan koneksi:

- Verifikasi TLS memakai CA sistem (`php.ini` `curl.cainfo`/`openssl.cafile`); bila tidak ada, otomatis memakai `cacert.pem` bawaan SDK Midtrans — jadi tidak wajib mengatur CA di tiap mesin.
- Saat koneksi gangguan sesaat atau upstream `5xx`, request diulang otomatis **1 kali** (delay 250 ms). Error `4xx` (mis. `422`) tidak diulang.
- Cek semua endpoint pihak ketiga sekaligus: `php artisan external:check` (RajaOngkir destinations & cost, Midtrans API). Exit code `1` bila ada yang gagal.

Jika muncul error `cURL error 60: SSL certificate problem`, artinya CA tidak ditemukan: isi `RAJAONGKIR_VERIFY=/path/ke/cacert.pem` (atau set `curl.cainfo` di `php.ini`). Lihat juga Troubleshooting.

### Pembayaran (Midtrans Snap Sandbox)

Konfigurasi `.env` (isi sendiri):

```
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_WEBHOOK_THROTTLE=60
```

> `MIDTRANS_SERVER_KEY` hanya untuk backend — jangan dibagikan ke frontend, log, dokumentasi, atau repo.

- POST `/orders/{id}/payment` (login, pemilik) → membuat transaksi Snap untuk pesanan `menunggu_pembayaran`; token `menunggu` yang masih berlaku dipakai ulang. `404` bila bukan milik user, `422` bila status bukan `menunggu_pembayaran`, `502` bila Midtrans gagal. Respons `201` (baru) / `200` (reuse), `data` berisi `snap_token`, `redirect_url`, dan `client_key`.

Frontend memakai `snap_token` bersama Snap.js dan `client_key` (keduanya aman untuk frontend).

- GET `/orders/{id}/payment` (login, pemilik) → pembayaran terbaru; `404` bila belum dibuat.
- GET `/admin/payments` (admin) → semua pembayaran. Query: `status`, `provider`, `page`, `per_page` (default 15).
- GET `/admin/payments/summary` (admin) → rekap pembayaran. Query: `status`, `provider`, `dari`, `sampai`. Contoh respons aktual: `{ "total_pembayaran": 4, "total_nilai": 475000, "nilai_dibayar": 250000, "per_status": { "menunggu": 2, "dibayar": 1, "ditolak": 0, "kedaluwarsa": 0, "dibatalkan": 1 }, "per_provider": { "manual": 1, "midtrans": 3 } }`.
- POST `/payments/midtrans/notification` (publik, untuk server Midtrans) → webhook. Signature diverifikasi `sha512(order_id + status_code + gross_amount + server_key)`; notifikasi dengan signature **valid** dicatat di tabel `webhook_logs`. Signature salah → `403` (hanya ke application log, tanpa menulis database); melebihi batas per IP (`throttle:midtrans-webhook`, default 60/menit, `MIDTRANS_WEBHOOK_THROTTLE`) → `429`; nominal tidak cocok → `200` `success:false`; notifikasi terminal ganda diabaikan (idempoten).

Pemetaan status transaksi:

| transaction_status | fraud_status | status_pembayaran | status_pesanan |
|---|---|---|---|
| settlement | - | `dibayar` | `dibayar` |
| capture | accept | `dibayar` | `dibayar` |
| capture | challenge | `menunggu` | (tetap) |
| pending | - | `menunggu` | (tetap) |
| deny | - | `ditolak` | `dibatalkan` |
| expire | - | `kedaluwarsa` | `dibatalkan` |
| cancel | - | `dibatalkan` | `dibatalkan` |

---

## Promo, Pengiriman, Pemenuhan & Konfigurasi (Admin)

Semua endpoint di bagian ini memakai `Authorization: Bearer {{adminToken}}`; tanpa token → `401`, memakai token pelanggan → `403`.

### Voucher — publikasi & ringkasan

- POST `/vouchers/{id}/publish` (admin) → `status_voucher` jadi `aktif`; idempoten (sudah aktif tetap `200`); voucher lewat `tanggal_selesai` → `422` `"Voucher sudah kedaluwarsa dan tidak dapat dipublikasikan."`; tidak ada → `404`.
- POST `/vouchers/{id}/unpublish` (admin) → kembali `nonaktif`, pesan `"Voucher berhasil ditarik dari publikasi."`.
- GET `/vouchers/summary` (admin) → contoh respons aktual:

```json
{
  "success": true,
  "message": "Ringkasan voucher berhasil diambil.",
  "data": { "total_voucher": 5, "aktif": 4, "nonaktif": 1, "berjalan": 2, "terjadwal": 1, "kadaluarsa": 1, "total_penggunaan": 2, "total_diskon_diberikan": 35000 }
}
```

### Pengiriman paket (tabel `shipments`, dari dump database)

- GET `/admin/shipments` (admin) → daftar pengiriman + relasi `order`. Query: `status` (`menunggu`/`diproses`/`dikirim`/`dalam_perjalanan`/`diterima`/`dibatalkan`), `kurir`, `search` (nomor resi / nomor pesanan), `dari`, `sampai`, `page`, `per_page`.
- PUT `/admin/shipments/{id}/status` (admin) body:

```json
{ "status_pengiriman": "dikirim", "nomor_resi": "RESI-2026-001", "lokasi": "Pekalongan", "keterangan": "Paket diserahkan ke kurir" }
```

Aturan: status sama → `422`; `dikirim`/`dalam_perjalanan` wajib `nomor_resi` (kalau tidak ada) → `422` `"Nomor resi wajib diisi sebelum paket dikirim."`; `dikirim` mengisi `tanggal_dikirim`, `diterima` mengisi `tanggal_diterima`; setiap perubahan dicatat ke `shipment_histories` (respons `200` menyertakan `histories` + `order`); tidak ada → `404`.

### Pemenuhan pesanan (tabel `order_fulfillments`, **migrasi baru**)

- GET `/admin/order-fulfillments` (admin) → daftar catatan + relasi `order` & `changed_by`. Query: `status` (`persiapan`/`dikemas`/`siap_kirim`/`selesai`), `id_order`, `page`, `per_page`.
- POST `/admin/orders/{id}/fulfillments` (admin) body `{ "status_fulfillment": "persiapan", "catatan": "Barang disiapkan dari gudang" }` → `201` + data catatan (termasuk `urutan` menaik per pesanan).

Aturan: pesanan `menunggu_pembayaran`/`dibatalkan`/`selesai` → `422`; status sama dengan catatan terakhir → `422` `"Status pemenuhan sama dengan catatan terakhir."`; pesanan tidak ada → `404`; status di luar daftar → `422`.

### Konfigurasi RajaOngkir (tabel `settings` + fallback `.env`)

- GET `/admin/shipping/config` → `{ "origin_id": "82935", "sumber_origin": "env", "base_url": "...", "api_key_terisi": true, "kurir_aktif": ["jne", ...] }` — **API key tidak pernah dikembalikan**.
- PUT `/admin/shipping/config` body `{ "origin_id": "99999" }` (wajib angka, maks 20) → tersimpan ke `settings`, `sumber_origin` jadi `settings`, dan langsung dipakai perhitungan ongkir. Tidak valid → `422`.
- GET `/admin/shipping/health` → `{ "status": "ok" | "degraded" | "belum_terkonfigurasi", "checks": { "api_key_terisi", "base_url_valid", "origin_terisi", "konektivitas_https" }, ..., "memakai_kuota_api": false }` — hanya cek konfigurasi + koneksi host, **tanpa memanggil API RajaOngkir** (kuota tidak berkurang).
- GET `/admin/shipping/couriers` → 10 kurir + `meta: { total, aktif }`.
- PUT `/admin/shipping/couriers/{code}` body `{ "aktif": false }` → kode tak dikenal `404`; menonaktifkan kurir terakhir → `422` `"Minimal satu kurir harus tetap aktif."`; kurir nonaktif langsung ditolak `POST /shipping/cost` & checkout (`422`).
- POST `/admin/shipping/simulate` body `{ "destination": 61622, "weight": 1000, "couriers": ["jne", "sicepat"] }` (`couriers` opsional — tanpa itu memakai semua kurir aktif) → respons:

```json
{
  "success": true,
  "message": "Simulasi ongkos kirim selesai.",
  "data": {
    "origin_id": "82935",
    "destination": 61622,
    "weight": 1000,
    "memakai_kuota_api": 2,
    "hasil": [
      { "kurir": "jne", "berhasil": true, "error": null, "layanan": [ { "name": "JNE", "code": "jne", "service": "REG", "description": "Layanan Reguler", "cost": 9000, "etd": "2-3" } ] },
      { "kurir": "sicepat", "berhasil": true, "error": null, "layanan": [ { "name": "SICEPAT", "code": "sicepat", "service": "REG", "description": "Layanan Reguler", "cost": 9000, "etd": "2-3" } ] }
    ]
  }
}
```

Kegagalan per kurir hanya mengisi entri `berhasil: false` + `error` (mis. `RAJAONGKIR_UPSTREAM_ERROR`) tanpa membatalkan kurir lain; tanpa origin → `502 RAJAONGKIR_NOT_CONFIGURED` tanpa panggilan API; kurir nonaktif → `422` `"Tidak ada kurir aktif yang diminta."`. **Endpoint ini memakai kuota RajaOngkir** (1 request per kurir).

---

# Cara menguji di Thunder Client (VS Code)

1. **Base URL env**: buat Environment bernama `Pandansari` dengan variabel
   `baseUrl = http://localhost:8000/api`. Pakai `{{baseUrl}}` di tiap request.
2. **Pastikan server hidup**: jalankan `php artisan serve` (atau server Herd), lalu
   GET `{{baseUrl}}/test-json` → harus muncul `TEST LARAVEL BERHASIL`.
3. **Ambil token admin**:
   - New Request → POST `{{baseUrl}}/login`
   - Tab **Body → JSON** → `{"email":"admin@example.com","kata_sandi":"admin12345"}` → **Send**
   - Simpan token ke env: klik kanan nilai `data.token` pada response →
     **Set as Environment Variable** (mis. `adminToken`).
   - Di request selanjutnya tambah header `Authorization: Bearer {{adminToken}}`
     dan `Accept: application/json`.
4. **Register + login pelanggan** untuk uji kepemilikan/403:
   - POST `{{baseUrl}}/register` (body di atas, email unik) → lalu POST `/login` →
     simpan sebagai `custToken`.
5. **Uji Alamat** (pakai `custToken`): GET list → POST (cek `alamat_utama` jadi `true`) →
   POST kedua `"alamat_utama": true` (cek list hanya satu true) →
   GET/PUT/DELETE `{id_user_address}`. Lalu ulangi GET memakai `adminToken` → harus **404**.
6. **Uji Varian**:
   - GET `{{baseUrl}}/products/3b7676c0-c898-11f1-8b52-002b6777bbb3/variants` (tanpa token, 200).
   - POST dengan `adminToken` (201); POST tanpa token (401); POST dengan `custToken` (403);
     POST `sku` sama (422); DELETE lalu GET lagi (varian hilang).
7. **Uji Gambar**:
   - GET images (tanpa token) → perhatikan `url`.
   - POST upload: tab **Body → Form**, tambah field **File** bernama `gambar`
     (pilih gambar ≤2MB) + field teks `alt_text`; header `Authorization: Bearer {{adminToken}}`
     → Send → `201`, buka `url` di browser.
   - DELETE `{{baseUrl}}/images/{id_image}` → `200`, lalu cek file di URL sudah hilang.
8. **Uji Regresi**: GET `/products`, `/products/{id}`, `/categories`, `/me` → tetap normal.
9. **Uji Checkout & Pembayaran** (`custToken`):
   - Tambah item ke keranjang: POST `/cart/items` dengan `id_variant` + `jumlah`.
   - Pastikan alamat punya `id_tujuan_rajaongkir` (ambil `id` dari GET `/shipping/destinations?search=surakarta`).
   - POST `/shipping/cost` `{ "destination": <id>, "weight": 1000, "courier": "jne" }` → catat salah satu `service`.
   - POST `/checkout` dengan `id_user_address` milik sendiri + `kurir` + `layanan` dari hasil di atas → simpan `data.id_order` → `201`.
   - GET `/orders` dan GET `/orders/{id_order}` → cek stok berkurang & status `menunggu_pembayaran`.
   - POST `/orders/{id_order}/payment` → simpan `data.snap_token`; panggil ulang → `200` (reuse).
   - GET `/orders/{id_order}/payment` → pastikan `status_pembayaran` = `menunggu`.
   - PUT `/admin/orders/{id_order}/status` (dengan `adminToken`) ke `diproses` → cek riwayat.
   - (Opsional) Simulasikan webhook: isi `.env` `MIDTRANS_SERVER_KEY`, hitung signature, lalu POST `/payments/midtrans/notification` dengan `order_id` = `nomor_pesanan`.

## ID siap pakai (data uji)

- Kategori `Batik Cap`: `9f5d8990-c871-11f1-8a96-002b6777bbb3`
- Produk `Batik Parang Klasik`: `3b7676c0-c898-11f1-8b52-002b6777bbb3`
- Produk `Batik Kawung Elegan`: `3b78a908-c898-11f1-8b52-002b6777bbb3`
- Varian `PARANG-3b7c252a`: `3b7c252a-c898-11f1-8b52-002b6777bbb3`
- Gambar `batik-parang.jpg`: `3b844d0c-c898-11f1-8b52-002b6777bbb3`
- Alamat milik `nabila@example.com`: `660e8400-e29b-41d4-a716-446655440000`

---

## Troubleshooting

- **`cURL error 60: SSL certificate problem`** — PHP tidak menemukan CA bundle. Pilihan: set `RAJAONGKIR_VERIFY=/path/ke/cacert.pem` di `.env`, atau isi `curl.cainfo`/`openssl.cafile` di `php.ini`. Kode sudah otomatis memakai `cacert.pem` bawaan SDK Midtrans bila CA sistem kosong.
- **`502 RAJAONGKIR_NOT_CONFIGURED`** — `RAJAONGKIR_API_KEY` atau `RAJAONGKIR_ORIGIN_ID` belum diisi.
- **`502 RAJAONGKIR_UNAUTHORIZED`** — API key salah/ditolak; cek juga kuota paket (Starter 100 request/hari).
- **`503 RAJAONGKIR_UNAVAILABLE`** — koneksi ke RajaOngkir benar-benar gagal (offline/DNS mati). Request gangguan sesaat sudah diulang otomatis 1 kali.
- **`422 ALAMAT_TANPA_TUJUAN_RAJAONGKIR`** — alamat belum punya `id_tujuan_rajaongkir`; isi lewat PUT `/addresses/{id}`.
- **`422 LAYANAN_TIDAK_TERSEDIA`** — `layanan` tidak ada pada hasil `POST /shipping/cost` untuk `kurir` tersebut.
- **Verifikasi cepat semua endpoint pihak ketiga**: `php artisan external:check`.
