# API Dokumentasi - Backend Batik Pandansari

Base URL: `http://localhost:8000/api` (sesuai `APP_URL`)

Semua endpoint diawali prefix `/api`.

---

## Format Response

Response standar:

```json
{
  "success": true,
  "message": "Deskripsi singkat.",
  "data": {}
}
```

Beberapa endpoint list (paginate) juga mengembalikan `meta`:

```json
{
  "success": true,
  "message": "...",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### Kode status umum

| Kode | Keterangan |
|---|---|
| 200 | OK |
| 201 | Created |
| 401 | Tidak terautentikasi (belum login / token invalid) |
| 403 | Akses ditolak (bukan admin) |
| 404 | Sumber daya tidak ditemukan |
| 422 | Data tidak valid / proses bisnis ditolak (mis. stok tidak cukup) |
| 500 | Kesalahan server |
| 502 | Gagal membuat transaksi pembayaran Midtrans |

### Error handling

- **401 Unauthorized** (belum login):
  ```json
  {
    "success": false,
    "message": "Tidak terautentikasi. Silakan login terlebih dahulu."
  }
  ```

- **403 Forbidden** (bukan admin):
  ```json
  {
    "success": false,
    "message": "Akses ditolak. Endpoint ini hanya untuk admin."
  }
  ```

- **422 Validation Error**:
  ```json
  {
    "success": false,
    "message": "Data yang dikirim tidak valid.",
    "errors": {
      "field": ["Pesan kesalahan per field."]
    }
  }
  ```

---

## Autentikasi

Menggunakan **Laravel Sanctum** (Personal Access Token). Login sekali, gunakan token di header semua request berikutnya:

```
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

Middleware:
- `auth:sanctum` — wajib login
- `admin` — wajib login **dan** role `admin` (dicek `EnsureUserIsAdmin`)

Role user: `pelanggan` | `admin`

---

## 1. Autentikasi

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/register` | Publik | Registrasi pelanggan baru |
| POST | `/login` | Publik | Login, mengembalikan token |
| POST | `/logout` | `auth:sanctum` | Logout (hapus token saat ini) |
| GET | `/me` | `auth:sanctum` | Profil user yang sedang login |

### POST `/register`

**Body:**

```json
{
  "nama_pengguna": "string (required, max:100)",
  "email": "string (required, email, max:150, unique)",
  "nomor_telepon": "string (nullable, max:20)",
  "kata_sandi": "string (required, min:8, confirmed)",
  "kata_sandi_confirmation": "string (required)"
}
```

**Response 201:**

```json
{
  "success": true,
  "message": "Registrasi berhasil.",
  "data": {
    "id_user": "uuid",
    "nama_pengguna": "Budi",
    "email": "budi@example.com",
    "nomor_telepon": "0812xxxx",
    "peran": "pelanggan",
    "dibuat_pada": "..."
  }
}
```

Role otomatis `pelanggan`.

### POST `/login`

**Body:**

```json
{
  "email": "budi@example.com",
  "kata_sandi": "rahasia123"
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "plainTextToken",
    "token_type": "Bearer",
    "user": {
      "id_user": "uuid",
      "nama_pengguna": "Budi",
      "email": "budi@example.com",
      "peran": "pelanggan"
    }
  }
}
```

**401** jika email/kata sandi salah:
```json
{
  "success": false,
  "message": "Email atau kata sandi salah."
}
```

### POST `/logout`

Menghapus token yang sedang dipakai. **Response 200:**

```json
{
  "success": true,
  "message": "Logout berhasil."
}
```

### GET `/me`

**Response 200:**

```json
{
  "success": true,
  "message": "Profil berhasil diambil.",
  "data": { "id_user": "uuid", "nama_pengguna": "...", "email": "...", "peran": "..." }
}
```

---

## 2. Users (Admin)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/users` | `auth:sanctum` + `admin` | Daftar semua user |

Query: `page`, `per_page` (default 15). Diurutkan by `nama_pengguna`.

Response `data` berisi item paginator + `meta`.

---

## 3. Kategori

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/categories` | Publik | Daftar kategori |
| GET | `/categories/{id}` | Publik | Detail kategori |

`{id}` = `id_category`. Diurutkan by `nama_kategori`. **404** jika tidak ditemukan.

---

## 4. Produk

### Publik

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/products` | Publik | List produk **aktif** |
| GET | `/products/{id}` | Publik | Detail produk **aktif** |

**Query GET `/products`:**

| Param | Tipe | Keterangan |
|---|---|---|
| `search` | string, max:150 | Cari `nama_produk` atau `deskripsi` (like) |
| `category` | string, max:36 | Filter `id_category` |
| `page` | int, min:1 | Halaman |
| `per_page` | int, min:1, max:100 | Default 10 |

Response menyertakan relasi `category`, `images` (urut: primary desc, urutan asc).

### Admin

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/admin/products` | `admin` | List semua produk (termasuk draft/nonaktif) |
| GET | `/admin/products/{id}` | `admin` | Detail produk (semua varian) |
| POST | `/products` | `admin` | Buat produk |
| PUT | `/products/{id}` | `admin` | Update produk |
| DELETE | `/products/{id}` | `admin` | Nonaktifkan produk |

**POST/PUT `/products` Body:**

```json
{
  "id_category": "string (required, exists:categories,id_category)",
  "nama_produk": "string (required, max:150)",
  "slug": "string (nullable, max:180, unique)",
  "deskripsi": "string (nullable)",
  "status_produk": "draft | aktif | nonaktif (nullable)"
}
```

Catatan:
- `slug` otomatis di-slugify. Jika kosong/null saat POST, di-generate unik dari `nama_produk` (mis. `batik-tulis-1`).
- Default `status_produk` saat POST: `draft`.
- `DELETE` = soft disable: set `status_produk = nonaktif` (bukan hard delete).
- **404** jika produk tidak ditemukan.

---

## 5. Varian Produk

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/products/{id}/variants` | Publik | Daftar varian **aktif** (hanya jika produk aktif) |
| POST | `/products/{id}/variants` | `admin` | Tambah varian |
| PUT | `/variants/{id}` | `admin` | Update varian |
| DELETE | `/variants/{id}` | `admin` | Nonaktifkan varian |

**POST `/products/{id}/variants` Body:**

```json
{
  "sku": "string (required, max:100, unique)",
  "nama_varian": "string (nullable, max:100)",
  "warna": "string (nullable, max:50)",
  "ukuran": "string (nullable, max:30)",
  "harga": "numeric (required, min:0, max:9999999999.99)",
  "stok": "integer (nullable, min:0, default 0)",
  "status_varian": "aktif | nonaktif (nullable, default aktif)"
}
```

**PUT `/variants/{id}` Body:** partial, semua field `sometimes`.
**DELETE** set `status_varian = nonaktif`.
**404** jika produk/varian tidak ditemukan.

---

## 6. Gambar Produk

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/products/{id}/images` | Publik | Daftar gambar produk |
| POST | `/products/{id}/images` | `admin` | Upload gambar (multipart) |
| DELETE | `/images/{id}` | `admin` | Hapus gambar + file fisik |

**POST `/products/{id}/images` (multipart/form-data):**

| Field | Tipe | Keterangan |
|---|---|---|
| `gambar` | file | required, image, mimes: jpg/jpeg/png/webp, max: 2048 KB |
| `alt_text` | string | nullable, max:150 |
| `is_primary` | boolean | nullable, jika true primary lain di-unset |
| `urutan` | integer | nullable, min:0; default = max(urutan)+1; jika gambar pertama otomatis primary |

Gambar disimpan di disk `public` (`storage/app/public/products/`). Response menyertakan `url` = `Storage::disk('public')->url(path_gambar)`.

Ordering response GET: `is_primary` desc, lalu `urutan` asc.

---

## 7. Alamat User (Login)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/addresses` | `auth:sanctum` | Daftar alamat milik user |
| POST | `/addresses` | `auth:sanctum` | Tambah alamat |
| GET | `/addresses/{id}` | `auth:sanctum` | Detail alamat (milik sendiri) |
| PUT | `/addresses/{id}` | `auth:sanctum` | Update alamat (milik sendiri) |
| DELETE | `/addresses/{id}` | `auth:sanctum` | Hapus alamat (milik sendiri) |

**Body POST/PUT:**

```json
{
  "label_alamat": "string (required/max:30)",
  "nama_penerima": "string (required/max:100)",
  "nomor_telepon": "string (required/max:20)",
  "nama_provinsi": "string (required/max:60)",
  "nama_kota": "string (required/max:60)",
  "nama_kecamatan": "string (required/max:60)",
  "kode_pos": "string (required/max:10)",
  "alamat_lengkap": "string (required)",
  "alamat_utama": "boolean (nullable)"
}
```

Jika `alamat_utama = true`, alamat lain milik user di-unset jadi false. Alamat pertama otomatis jadi utama. Ordering: `alamat_utama` desc, `dibuat_pada` desc. **404** jika tidak ditemukan / bukan milik user.

---

## 8. Keranjang (Login, milik sendiri)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/cart` | `auth:sanctum` | Ambil keranjang aktif user |
| POST | `/cart/items` | `auth:sanctum` | Tambah item (merge jika variant sama) |
| PUT | `/cart/items/{id}` | `auth:sanctum` | Update jumlah item |
| DELETE | `/cart/items/{id}` | `auth:sanctum` | Hapus item |
| DELETE | `/cart` | `auth:sanctum` | Kosongkan keranjang |

Keranjang dibuat otomatis via `Cart::firstOrCreate` (`status_cart = aktif`).

**POST `/cart/items` Body:**

```json
{
  "id_variant": "string (required, exists:product_variants,id_variant)",
  "jumlah": "integer (required, min:1)"
}
```

**PUT `/cart/items/{id}` Body:**

```json
{ "jumlah": "integer (required, min:1)" }
```

Validasi:
- Varian harus `aktif` **dan** produk harus `aktif` → jika tidak: **422** `"Varian produk tidak tersedia untuk dibeli."`
- Jika `jumlah > stok` → **422** `"Stok tidak mencukupi. Stok tersedia: {stok}."`
- POST merge jumlah existing + baru, total tidak boleh > stok
- **404** item keranjang tidak ditemukan / bukan milik user

Response menyertakan relasi `variant.product`.

**Catatan (frontend):** `variant.harga` datang sebagai string (`"180000.00"`) — sudah di-`Number()` di frontend. `variant.product` **belum menyertakan `images`/`category`** — jika bisa, tolong eager-load `variant.product.images` supaya gambar produk tampil di halaman keranjang/checkout (saat ini memakai gambar fallback).

---

## 9. Wishlist (Login, milik sendiri)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/wishlist` | `auth:sanctum` | Daftar wishlist user |
| POST | `/wishlist` | `auth:sanctum` | Tambah produk ke wishlist |
| DELETE | `/wishlist/{id}` | `auth:sanctum` | Hapus item wishlist |

**POST `/wishlist` Body:**

```json
{ "id_product": "string (required, exists:products,id_product)" }
```

Validasi:
- Produk harus `aktif` → jika tidak: **404** `"Produk tidak ditemukan atau tidak tersedia."`
- Tidak boleh duplikat → **422** `"Produk sudah ada di wishlist."`

GET menyertakan relasi `product.category`, `product.variants` (aktif), `product.images` (urut). **404** jika item wishlist bukan milik user.

**Catatan (penting untuk frontend):** field ID item wishlist pada respons GET & POST adalah **`id_wishlist_item`** (bukan `id_wishlist`/`id`) — gunakan ini untuk `DELETE /wishlist/{id}`.

---

## 10. Voucher

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/vouchers` | `admin` | List voucher + search |
| POST | `/vouchers` | `admin` | Buat voucher |
| GET | `/vouchers/{id}` | `admin` | Detail voucher |
| PUT | `/vouchers/{id}` | `admin` | Update voucher |
| DELETE | `/vouchers/{id}` | `admin` | Nonaktifkan voucher |
| GET | `/vouchers/available` | `auth:sanctum` | Voucher tersedia untuk user |
| POST | `/vouchers/validate` | `auth:sanctum` | Validasi voucher untuk checkout |
| GET | `/voucher-usages` | `admin` | Riwayat penggunaan voucher (semua user) |
| GET | `/voucher-usages/me` | `auth:sanctum` | Riwayat penggunaan voucher milik sendiri |

**POST `/vouchers` Body:**

```json
{
  "kode_voucher": "string (required, max:50, unique)",
  "nama_voucher": "string (required, max:100)",
  "deskripsi": "string (nullable)",
  "tipe_diskon": "persentase | nominal (required)",
  "nilai_diskon": "numeric (required, min:0, max:9999999999.99)",
  "minimal_pembelian": "numeric (nullable, min:0, default 0)",
  "maksimal_diskon": "numeric (nullable, min:0)",
  "kuota_total": "integer (nullable, min:0)",
  "kuota_per_pengguna": "integer (nullable, min:0, default 1)",
  "tanggal_mulai": "date (required)",
  "tanggal_selesai": "date (required, after_or_equal:tanggal_mulai)",
  "status_voucher": "aktif | nonaktif (nullable, default aktif)"
}
```

**GET `/vouchers/available` Query:**

| Param | Tipe | Keterangan |
|---|---|---|
| `subtotal` | numeric, nullable | Hanya tampilkan voucher dengan `minimal_pembelian <= subtotal` |

Filter: `status_voucher = aktif`, `tanggal_mulai <= now`, `tanggal_selesai >= now`, lolos kuota.

**POST `/vouchers/validate` Body:**

```json
{ "kode_voucher": "string (required, max:50)", "subtotal": "numeric (required, min:0)" }
```

Jika valid → **200** dengan info voucher + diskon. Jika tidak valid → **422** dengan pesan:
- `"Kode voucher tidak ditemukan."`
- `"Voucher tidak aktif."`
- `"Voucher belum berlaku."`
- `"Voucher sudah kedaluwarsa."`
- `"Minimal pembelian untuk voucher ini adalah Rp{minimal}."`
- `"Kuota voucher sudah habis atau sudah Anda gunakan."`

**Perhitungan diskon:**
- `persentase` → `subtotal * nilai_diskon / 100`, dibatasi `maksimal_diskon` (jika ada), maksimal `subtotal`
- `nominal` → `min(nilai_diskon, subtotal)`

**Kuota** dihitung dari `voucher_usages` dengan `status_penggunaan IN ('menunggu','digunakan')` — cek `kuota_total` dan `kuota_per_pengguna`.

Status penggunaan voucher: `menunggu` | `digunakan` | `dibatalkan`.

---

## 11. Checkout (Login)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/checkout` | `auth:sanctum` | Buat pesanan dari keranjang aktif |

**Body:**

```json
{
  "id_user_address": "string (required)",
  "kode_voucher": "string (nullable, max:50)",
  "biaya_pengiriman": "numeric (nullable, min:0, max:9999999999.99)",
  "catatan": "string (nullable)"
}
```

**Proses (dalam transaksi DB):**
1. Validasi alamat milik user → **404** jika tidak ada
2. Ambil keranjang aktif + items → **422** `"Keranjang Anda kosong."` jika kosong
3. Lock setiap variant, cek variant aktif + produk aktif + stok cukup → **422** jika tidak
4. Hitung `subtotal`; jika ada `kode_voucher`, validasi via `VoucherService` → **422** jika tidak valid
5. `total = max(0, subtotal + biaya_pengiriman - diskon)`
6. Buat order (`nomor_pesanan` unik format `ORD-YYYYMMDD-XXXX`, status `menunggu_pembayaran`)
7. Buat `order_items`, kurangi stok variant, catat `inventory_movement` (jenis `keluar`, referensi `nomor_pesanan`)
8. Jika voucher → buat `voucher_usages` status `menunggu`
9. Catat `order_status_history` (null → `menunggu_pembayaran`)
10. Set keranjang `status_cart = checkout`

**Response 201:** order + items.

**500** jika gagal diproses:
```json
{
  "success": false,
  "message": "Checkout gagal diproses. Silakan coba lagi."
}
```

---

## 12. Pesanan (Order)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/orders` | `auth:sanctum` | Daftar pesanan milik user |
| GET | `/orders/{id}` | `auth:sanctum` | Detail pesanan milik user |
| GET | `/admin/orders` | `admin` | Daftar semua pesanan |
| GET | `/admin/orders/{id}` | `admin` | Detail lengkap pesanan |
| PUT | `/admin/orders/{id}/status` | `admin` | Update status pesanan |

**Status pesanan:** `menunggu_pembayaran` | `dibayar` | `diproses` | `dikirim` | `selesai` | `dibatalkan`

**Query GET `/orders`:**

| Param | Tipe | Keterangan |
|---|---|---|
| `status` | string, in status di atas | Filter status |
| `page` | int | Halaman |
| `per_page` | int, 1-100 | Default 10 |

**Query GET `/admin/orders`:**

| Param | Tipe | Keterangan |
|---|---|---|
| `status` | string | Filter status |
| `search` | string, max:150 | Cari `nomor_pesanan` atau `nama_penerima` |
| `dari` | date | Filter `dibuat_pada >= dari` |
| `sampai` | date | Filter `dibuat_pada <= sampai` |
| `page` | int | Halaman |
| `per_page` | int, 1-100 | Default 15 |

GET `/orders` menyertakan `items`. GET `/orders/{id}` menyertakan `items.variant.product`, `payments`, `voucherUsages.voucher`, `statusHistories`.
GET `/admin/orders` menyertakan `items`, `user`. GET `/admin/orders/{id}` menyertakan `user`, `items.variant.product`, `payments`, `voucherUsages.voucher`, `statusHistories.changedBy`.

**PUT `/admin/orders/{id}/status` Body:**

```json
{
  "status_pesanan": "menunggu_pembayaran | dibayar | diproses | dikirim | selesai | dibatalkan (required)",
  "catatan": "string (nullable)"
}
```

**Logika `OrderService::changeStatus`:**
- Jika status sama → return order (tanpa history)
- Jika ke `dibatalkan` dari `menunggu_pembayaran`/`dibayar` → **restore stok** (lock variant, `+jumlah`, catat `inventory_movement` jenis `masuk`, referensi `nomor_pesanan`, catatan `"Pengembalian stok pembatalan pesanan"`), dan `voucher_usages` (`menunggu`/`digunakan` → `dibatalkan`)
- Jika ke `dibayar` → `voucher_usages` `menunggu` → `digunakan` + `digunakan_pada`
- Catat `order_status_history` (`status_sebelumnya`, `status_baru`, `catatan`, `diubah_oleh`)
- Update `status_pesanan`

**404** jika pesanan tidak ditemukan / bukan milik user.

---

## 13. Pembayaran (Midtrans)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/orders/{id}/payment` | `auth:sanctum` | Buat transaksi pembayaran Midtrans Snap |
| GET | `/orders/{id}/payment` | `auth:sanctum` | Detail pembayaran terakhir untuk order |
| GET | `/admin/payments` | `admin` | Daftar semua pembayaran |
| POST | `/payments/midtrans/notification` | Publik | Webhook notifikasi Midtrans |

### POST `/orders/{id}/payment`

Syarat: order milik user **dan** `status_pesanan == menunggu_pembayaran` (selain itu **422** `"Pesanan ini tidak dapat dibayar."`).

Logika:
- Jika ada payment terakhir berstatus `menunggu` dengan `payment_reference` dan (`expires_at` null / masih future) → **reuse** (**200**)
- Jika tidak → buat Payment (status `menunggu`), panggil Midtrans Snap. Jika gagal → hapus payment & **502**. Update `payment_reference` (snap token), `payment_url`, `expires_at = now() + 1 hari`. Return **201**.

**Response data:**

```json
{
  "id_payment": "uuid",
  "id_order": "uuid",
  "nomor_pesanan": "ORD-20250101-0001",
  "provider": "midtrans",
  "status_pembayaran": "menunggu",
  "jumlah_pembayaran": 123456.00,
  "snap_token": "...",
  "redirect_url": "https://app.sandbox.midtrans.com/snap/v2/redirection/...",
  "client_key": "SB-Mid-client-...",
  "expires_at": "..."
}
```

**MidtransService:**
- Config: `serverKey`, `clientKey`, `isProduction`, `isSanitized`, `is3ds`. Jika `curl.cainfo`/`openssl.cafile` kosong → pakai `cacert.pem` bawaan SDK.
- `createSnapTransaction`: `gross_amount = round(total_pembayaran)` (integer). Sertakan `customer_details` (nama penerima, telepon, shipping address, email jika ada).
- `notification()` → objek `Midtrans\Notification` (status authoritative dari Midtrans).
- `verifySignature`: `sha512(order_id + status_code + gross_amount + server_key)`, dicek dengan `hash_equals`.

Konfigurasi di `config/services.php` → env: `MIDTRANS_MERCHANT_ID`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION`.

### GET `/orders/{id}/payment`

Ambil payment terakhir (by `dibuat_pada` desc) untuk order milik user. **404** jika order tidak ada / bukan milik user / belum ada payment.

### GET `/admin/payments`

Query: `status` (status_pembayaran), `provider`, `page`, `per_page` (default 15). Menyertakan relasi `order.user`.

### POST `/payments/midtrans/notification` (Webhook)

Endpoint publik (tanpa auth). Dipanggil Midtrans. Verifikasi pakai signature + SDK.

**Alur:**
1. Log ke `webhook_logs` (provider, payload mentah, `signature_key`, IP, endpoint, method)
2. Verifikasi signature via `MidtransService::verifySignature` → jika gagal: log status `gagal`, **403** `"Signature notifikasi tidak valid."`
3. Ambil notification authoritative via `MidtransService::notification()`
4. Cari order by `nomor_pesanan` → jika tidak ada: **200** (ack, tapi log `gagal`)
5. Cocokkan `gross_amount` (2 desimal) dengan `total_pembayaran` order → jika beda: **200** (ack, log `gagal`)
6. Mapping status transaksi Midtrans → status payment + status order:

| transaction_status | fraud_status | payment | order | paid |
|---|---|---|---|---|
| capture | accept | dibayar | dibayar | true |
| capture | challenge | menunggu | null | false |
| capture | (lain) | ditolak | dibatalkan | false |
| settlement | - | dibayar | dibayar | true |
| pending | - | menunggu | null | false |
| deny | - | ditolak | dibatalkan | false |
| expire | - | kedaluwarsa | dibatalkan | false |
| cancel | - | dibatalkan | dibatalkan | false |

7. Dalam transaksi DB: lock payment terakhir. Jika sudah terminal (`dibayar`/`ditolak`/`kedaluwarsa`/`dibatalkan`) → duplikat, diabaikan (ack). Update payment (`provider_transaction_id`, `metode_pembayaran`, `status_pembayaran`, `paid_at` jika paid). Update order via `OrderService::changeStatus` jika `mapping.order` tidak null & beda.
8. Log status `diproses`/`ganda`, **200**.

Response sukses:
```json
{
  "success": true,
  "message": "Notifikasi berhasil diproses."
}
```

---

## 14. Pengiriman (Shipments, Admin)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/admin/shipments` | `admin` | Daftar shipment + relasi `order` |
| PUT | `/admin/shipments/{id}/status` | `admin` | Update `status_pengiriman` |

**Query GET `/admin/shipments`:** `status` (status_pengiriman), `kurir`, `page`, `per_page` (default 15). Meta paginator: `{current_page, per_page, total, last_page}`.

**Field item shipment (live probe):**

```json
{
  "id_shipment": "uuid", "id_order": "uuid",
  "provider": "rajaongkir", "kurir": "JNE", "layanan_pengiriman": "REG",
  "kode_layanan": null, "berat_gram": 500, "biaya_pengiriman": "18000.00",
  "nomor_resi": null, "status_pengiriman": "menunggu",
  "tanggal_dikirim": null, "tanggal_diterima": null,
  "dibuat_pada": "...", "diperbarui_pada": "...",
  "order": { "id_order": "...", "nomor_pesanan": "...", "...": "relasi order" }
}
```

`status_pengiriman` ∈ `menunggu | diproses | dikirim | diterima | dibatalkan | dalam_perjalanan`.

**GET `/admin/order-fulfillments`** — daftar fulfillment (packing): `{ id_fulfillment, id_order, status_fulfillment (dikemas|selesai), ... }`, meta paginator. **Catatan:** respons GET `/admin/orders/{id}` TIDAK menyertakan `fulfillments` — frontend memuatnya via endpoint ini.

---

## 15. Mesin Ekspedisi (Admin Shipping)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/admin/shipping/config` | `admin` | Konfigurasi RajaOngkir |
| GET | `/admin/shipping/health` | `admin` | Cek kesehatan koneksi |
| GET | `/admin/shipping/couriers` | `admin` | Daftar kurir + status aktif |
| PUT | `/admin/shipping/couriers/{code}` | `admin` | Aktif/nonaktifkan kurir `{ aktif: bool }` |
| POST | `/admin/shipping/simulate` | `admin` | Simulasi tarif (**memakai kuota API**) |

**Respons live:**

```json
// GET /admin/shipping/config
{ "origin_id": "99999", "sumber_origin": "env",
  "base_url": "https://rajaongkir.komerce.id/api/v1",
  "api_key_terisi": true, "kurir_aktif": ["jne","tiki","sicepat","jnt","anteraja","jxpress","lex","lion","ninja","pos"] }

// GET /admin/shipping/health
{ "status": "ok", "checks": { "api_key_terisi": true, "base_url_valid": true, "origin_terisi": true, "konektivitas_https": true },
  "base_url": "...", "origin_id": "99999", "jumlah_kurir_aktif": 10, "memakai_kuota_api": false }

// GET /admin/shipping/couriers → data: [ { "kode": "jne", "aktif": true }, ... ], meta: { total: 10, aktif: 10 }
```

**POST `/admin/shipping/simulate` Body:** `{ destination: int (id hasil /shipping/destinations), weight: int (gram), courier: string|null }`.

> ⚠️ **BUG DILAPORKAN (per 20 Okt 2026):** `/shipping/cost` (publik login) dan `/admin/shipping/simulate` selalu **422 `RAJAONGKIR_DESTINATION_NOT_FOUND`** untuk SEMUA format tujuan (id search, city id, zip, string) walau config sehat (origin `99999`, base `rajaongkir.komerce.id`). Menunggu perbaikan backend; checkout pelanggan fallback ke tarif manual statis.

---

## 16. Ringkasan & Ekspor (Admin)

| Method | Endpoint | Respons `data` |
|---|---|---|
| GET | `/admin/orders/summary` | `{ total_pesanan, total_nilai, rata_rata_nilai, per_status: {…} }` |
| GET | `/admin/payments/summary` | `{ total_pembayaran, total_nilai, nilai_dibayar, per_status: {…}, per_provider: {midtrans: n, sandbox: n} }` |
| GET | `/admin/users/summary` | `{ total_pengguna, pengguna_aktif, per_peran: {pelanggan: n, admin: n} }` |
| GET | `/vouchers/summary` | `{ total_voucher, aktif, nonaktif, berjalan, terjadwal, kadaluarsa, total_penggunaan, total_diskon_diberikan }` |
| GET | `/users/export` | CSV (text/csv) seluruh pengguna |
| GET | `/admin/products/export` | CSV (text/csv) seluruh produk |

**`GET /admin/payments` Query:** `status` (status_pembayaran: `menunggu|dibayar|ditolak|kedaluwarsa|dibatalkan`), `provider` (`midtrans|sandbox`), `page`, `per_page`. Field item (live probe):

```json
{
  "id_payment": "uuid", "id_order": "uuid",
  "provider": "midtrans", "provider_transaction_id": null,
  "metode_pembayaran": null, "jumlah_pembayaran": "184000.00",
  "status_pembayaran": "menunggu",
  "payment_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/...",
  "payment_reference": "snap-token", "expires_at": "...", "paid_at": null,
  "dibuat_pada": "...", "diperbarui_pada": "...",
  "order": { "nomor_pesanan": "...", "user": { "nama_pengguna": "...", "email": "..." } }
}
```

---

## 17. Kontrak Live Tambahan (Probe 20 Okt 2026)

**GET `/admin/orders` (list) — field per item:**

```json
{
  "id_order": "uuid", "id_user": "uuid", "nomor_pesanan": "ORD-20261019-0002",
  "status_pesanan": "menunggu_pembayaran",
  "subtotal": "180000.00", "biaya_pengiriman": "22000.00",
  "diskon": "18000.00", "total_pembayaran": "184000.00",
  "nama_penerima": "...", "nomor_telepon_penerima": "0812...",
  "alamat_pengiriman": "Jl. ..., Laweyan, Surakarta, Jawa Tengah, 57141",
  "kode_tujuan_pengiriman": "Surakarta", "catatan": "...",
  "dibuat_pada": "...", "diperbarui_pada": "...",
  "items": [ { "id_order_item": "uuid", "id_variant": "uuid",
    "nama_produk": "...", "sku": "...", "warna": "Cokelat", "ukuran": "M",
    "harga_satuan": "180000.00", "jumlah": 1, "subtotal": "180000.00", "variant": {…} } ],
  "user": { "id_user": "uuid", "nama_pengguna": "...", "email": "...", "nomor_telepon": "...", "peran": "pelanggan" }
}
```

> Penting: **tidak ada** `nama_kota`/`nama_kecamatan` terpisah — alamat sebagai string `alamat_pengiriman` + `kode_tujuan_pengiriman`. Telepon = `nomor_telepon_penerima` (bukan `nomor_telepon`).

**GET `/admin/orders/{id}` — tambahan relasi:** `payments[]` (lihat §16), `voucher_usages[]`, `status_histories[]`:

```json
{ "id_order_status_history": "uuid", "status_sebelumnya": null,
  "status_baru": "menunggu_pembayaran", "catatan": "Pesanan dibuat.",
  "diubah_oleh": "uuid", "waktu_perubahan": "...",
  "changed_by": { "id_user": "...", "nama_pengguna": "..." } }
```

> Field `status_histories[].status_pesanan` TIDAK ada — gunakan `status_baru`. Waktu = `waktu_perubahan`.

**GET `/vouchers` — field per item:** `id_voucher, kode_voucher, nama_voucher, deskripsi, tipe_diskon, nilai_diskon, minimal_pembelian, maksimal_diskon, kuota_total, kuota_per_pengguna, tanggal_mulai, tanggal_selesai, status_voucher, dibuat_pada, diperbarui_pada`. (Tidak ada `total_penggunaan` per baris — ambil dari `/vouchers/summary`.)

**GET `/users` — paginator:** beda dari endpoint lain — seluruh paginator (`data[]` + `current_page/per_page/total/last_page`) berada DI DALAM `data`. Helper `PandansariAPI.admin.users()` sudah menormalkan ke `{data, meta}`.

**GET `/shipping/destinations?search=`** — param literal `search`, min. 3 karakter:

```json
{ "id": 17466, "label": "SURAKARTA, SURANENGGALA, CIREBON, JAWA BARAT, 45152",
  "province_name": "JAWA BARAT", "city_name": "CIREBON",
  "district_name": "SURANENGGALA", "subdistrict_name": "SURAKARTA", "zip_code": "45152" }
```

---

## Catatan Teknis

- ID semua entitas berupa **UUID string** (`id_user`, `id_product`, `id_variant`, `id_order`, dll).
- Timestamp kolom: `dibuat_pada` (created_at), `diperbarui_pada` (updated_at).
- Field bahasa Indonesia: `nama_pengguna`, `kata_sandi`, `peran`, `status_produk`, `status_varian`, `status_pesanan`, `status_pembayaran`, `status_voucher`, `status_penggunaan`, `status_cart`, dll.
- Diskon/voucher: `tipe_diskon` ∈ `persentase`/`nominal`.
- Pembayaran via Midtrans Snap. Webhook publik harus selalu diverifikasi signature.
- Endpoint `/test-json` hanya untuk health check cepat, return teks `"TEST LARAVEL BERHASIL"`.
