# Daftar API Backend Pandansari

Dokumentasi seluruh endpoint API yang tersedia pada project `backend-pandansari` (Laravel).

Total: **54 endpoint**.

## Informasi Umum

| Item | Nilai |
|---|---|
| Base URL | `http://localhost:8000` (development) |
| Prefix API | `/api` |
| Format | JSON |
| Autentikasi | Laravel Sanctum (Bearer Token) |
| Header auth | `Authorization: Bearer <token>` |
| Header body JSON | `Content-Type: application/json` |
| Upload file | `Content-Type: multipart/form-data` |

### Peran (Role)

- **publik** - tanpa login.
- **login** - butuh header `Authorization: Bearer <token>`.
- **admin** - login DAN `peran = admin`. Selain admin akan menerima `403`.

### Format Respons Sukses

```json
{
  "success": true,
  "message": "Pesan berhasil.",
  "data": {}
}
```

Untuk endpoint berpaginasi ditambahkan `meta`:

```json
{
  "success": true,
  "message": "Daftar ... berhasil diambil.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 0,
    "last_page": 1
  }
}
```

### Format Respons Gagal

```json
{
  "success": false,
  "message": "Pesan kesalahan."
}
```

Validasi gagal (422) memakai format bawaan Laravel:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Pesan validasi."]
  }
}
```

### Status Code Umum

| Code | Arti |
|---|---|
| 200 | Berhasil |
| 201 | Berhasil membuat data baru |
| 401 | Belum/tidak terautentikasi |
| 403 | Tidak punya hak akses (bukan admin) |
| 404 | Data tidak ditemukan |
| 422 | Validasi gagal / data tidak valid |
| 500 | Kesalahan server |

---

## 1. Autentikasi & Pengguna

### 1.1. Register
- **Method / Path**: `POST /api/register`
- **Akses**: publik
- **Deskripsi**: Mendaftarkan akun pelanggan baru.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| nama_pengguna | string | ya | maks 100 |
| email | string | ya | format email, unik, maks 150 |
| nomor_telepon | string | tidak | maks 20 |
| kata_sandi | string | ya | min 8, harus ada `kata_sandi_confirmation` |

Contoh request:

```json
{
  "nama_pengguna": "Budi",
  "email": "budi@example.com",
  "nomor_telepon": "08123456789",
  "kata_sandi": "rahasia123",
  "kata_sandi_confirmation": "rahasia123"
}
```

Respons `201`: `data` berisi objek user.

### 1.2. Login
- **Method / Path**: `POST /api/login`
- **Akses**: publik
- **Deskripsi**: Login dan mendapatkan token Sanctum.

Body: `email` (wajib), `kata_sandi` (wajib).

Respons `200`:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "1|xxxxxxxx",
    "token_type": "Bearer",
    "user": {}
  }
}
```

`401` jika email/kata sandi salah.

### 1.3. Logout
- **Method / Path**: `POST /api/logout`
- **Akses**: login
- **Deskripsi**: Menghapus token yang sedang dipakai.

Respons `200`: `{ "success": true, "message": "Logout berhasil." }`

### 1.4. Profil Saya
- **Method / Path**: `GET /api/me`
- **Akses**: login
- **Deskripsi**: Mengambil data user yang sedang login.

Respons `200`: `data` berisi objek user.

### 1.5. Daftar Pengguna
- **Method / Path**: `GET /api/users`
- **Akses**: admin
- **Deskripsi**: Menampilkan semua pengguna (paginasi 15 per halaman).

Respons `200`: `data` berisi objek paginator (termasuk `data`, `current_page`, `total`, dll).

---

## 2. Alamat

Semua endpoint alamat hanya mengakses data milik user yang login.

### 2.1. Daftar Alamat
- **Method / Path**: `GET /api/addresses`
- **Akses**: login
- **Deskripsi**: Semua alamat milik user, urut alamat utama lalu terbaru.

### 2.2. Tambah Alamat
- **Method / Path**: `POST /api/addresses`
- **Akses**: login

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| label_alamat | string | ya | maks 30 |
| nama_penerima | string | ya | maks 100 |
| nomor_telepon | string | ya | maks 20 |
| nama_provinsi | string | ya | maks 60 |
| nama_kota | string | ya | maks 60 |
| nama_kecamatan | string | ya | maks 60 |
| kode_pos | string | ya | maks 10 |
| alamat_lengkap | string | ya | - |
| alamat_utama | boolean | tidak | alamat pertama otomatis jadi utama |

Respons `201`: `data` objek alamat.

### 2.3. Detail Alamat
- **Method / Path**: `GET /api/addresses/{id}`
- **Akses**: login (pemilik)
- **404** jika bukan milik user.

### 2.4. Ubah Alamat
- **Method / Path**: `PUT /api/addresses/{id}`
- **Akses**: login (pemilik)
- Body: field sama seperti tambah, semua opsional (`sometimes`).

### 2.5. Hapus Alamat
- **Method / Path**: `DELETE /api/addresses/{id}`
- **Akses**: login (pemilik)

---

## 3. Kategori

### 3.1. Daftar Kategori
- **Method / Path**: `GET /api/categories`
- **Akses**: publik
- **Deskripsi**: Semua kategori, urut nama.

### 3.2. Detail Kategori
- **Method / Path**: `GET /api/categories/{id}`
- **Akses**: publik
- **404** jika tidak ditemukan.

---

## 4. Produk

### 4.1. Daftar Produk (Publik)
- **Method / Path**: `GET /api/products`
- **Akses**: publik
- **Deskripsi**: Hanya produk `aktif`, dengan kategori dan gambar. Paginasi.

Query:

| Param | Tipe | Keterangan |
|---|---|---|
| search | string | cari di `nama_produk` / `deskripsi` |
| category | string | filter `id_category` |
| page | integer | halaman (min 1) |
| per_page | integer | 1-100, default 10 |

### 4.2. Detail Produk (Publik)
- **Method / Path**: `GET /api/products/{id}`
- **Akses**: publik
- **Deskripsi**: Produk `aktif` beserta varian `aktif` dan gambar.

### 4.3. Tambah Produk
- **Method / Path**: `POST /api/products`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_category | string | ya | harus ada di tabel kategori |
| nama_produk | string | ya | maks 150 |
| slug | string | tidak | unik, maks 180; dibuat otomatis bila kosong |
| deskripsi | string | tidak | - |
| status_produk | string | tidak | `draft` / `aktif` / `nonaktif` (default `draft`) |

Respons `201`.

### 4.4. Ubah Produk
- **Method / Path**: `PUT /api/products/{id}`
- **Akses**: admin
- Body: field sama, semua opsional.

### 4.5. Hapus Produk
- **Method / Path**: `DELETE /api/products/{id}`
- **Akses**: admin
- **Deskripsi**: Soft delete - mengubah `status_produk` menjadi `nonaktif`.

### 4.6. Daftar Produk (Admin)
- **Method / Path**: `GET /api/admin/products`
- **Akses**: admin
- **Deskripsi**: Semua produk (termasuk `draft`/`nonaktif`). Query sama dengan endpoint publik.

### 4.7. Detail Produk (Admin)
- **Method / Path**: `GET /api/admin/products/{id}`
- **Akses**: admin
- **Deskripsi**: Semua varian (termasuk `nonaktif`).

---

## 5. Varian Produk

### 5.1. Daftar Varian
- **Method / Path**: `GET /api/products/{id}/variants`
- **Akses**: publik
- **Deskripsi**: Varian `aktif` dari produk `aktif`. `404` bila produk tidak ditemukan/tidak aktif.

### 5.2. Tambah Varian
- **Method / Path**: `POST /api/products/{id}/variants`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| sku | string | ya | unik, maks 100 |
| nama_varian | string | tidak | maks 100 |
| warna | string | tidak | maks 50 |
| ukuran | string | tidak | maks 30 |
| harga | numeric | ya | min 0 |
| stok | integer | tidak | min 0 (default 0) |
| status_varian | string | tidak | `aktif` / `nonaktif` (default `aktif`) |

Respons `201`.

### 5.3. Ubah Varian
- **Method / Path**: `PUT /api/variants/{id}`
- **Akses**: admin
- Body: field sama, semua opsional.

### 5.4. Hapus Varian
- **Method / Path**: `DELETE /api/variants/{id}`
- **Akses**: admin
- **Deskripsi**: Soft delete - `status_varian` menjadi `nonaktif`.

---

## 6. Gambar Produk

### 6.1. Daftar Gambar
- **Method / Path**: `GET /api/products/{id}/images`
- **Akses**: publik
- **Deskripsi**: Gambar produk `aktif`, urut gambar utama lalu `urutan`. Setiap item memiliki `url`.

### 6.2. Unggah Gambar
- **Method / Path**: `POST /api/products/{id}/images`
- **Akses**: admin
- **Content-Type**: `multipart/form-data`

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| gambar | file | ya | jpg/jpeg/png/webp, maks 2048 KB |
| alt_text | string | tidak | maks 150 |
| is_primary | boolean | tidak | gambar pertama otomatis utama |
| urutan | integer | tidak | min 0 (default lanjut dari maks) |

Respons `201`.

### 6.3. Hapus Gambar
- **Method / Path**: `DELETE /api/images/{id}`
- **Akses**: admin
- **Deskripsi**: Menghapus file dan record gambar.

---

## 7. Wishlist

Hanya mengakses data milik user yang login. `id_user` diambil dari token.

### 7.1. Daftar Wishlist
- **Method / Path**: `GET /api/wishlist`
- **Akses**: login
- **Deskripsi**: Item wishlist user beserta produk (kategori, varian `aktif`, gambar), urut terbaru.

### 7.2. Tambah Wishlist
- **Method / Path**: `POST /api/wishlist`
- **Akses**: login

Body: `id_product` (wajib, harus ada di tabel produk).

- Produk harus berstatus `aktif`, jika tidak → `404`.
- Bila produk sudah ada di wishlist → `422`.
- Respons `201`.

### 7.3. Hapus Wishlist
- **Method / Path**: `DELETE /api/wishlist/{id}`
- **Akses**: login (pemilik)
- **404** bila item tidak ada / bukan milik user.

---

## 8. Keranjang (Cart)

Hanya mengakses keranjang milik user yang login. Keranjang `aktif` dibuat otomatis bila belum ada.

### 8.1. Lihat Keranjang
- **Method / Path**: `GET /api/cart`
- **Akses**: login
- **Deskripsi**: Keranjang aktif beserta item dan varian/produknya.

### 8.2. Tambah Item
- **Method / Path**: `POST /api/cart/items`
- **Akses**: login

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_variant | string | ya | harus ada di tabel varian |
| jumlah | integer | ya | min 1 |

- Bila varian sudah ada di keranjang, jumlah ditambahkan (merge).
- Varian/produk harus `aktif`, jika tidak → `422`.
- Bila total melebihi stok → `422` (`Stok tidak mencukupi. Stok tersedia: N.`).
- Respons `201` (item baru) atau `200` (update jumlah).

### 8.3. Ubah Jumlah Item
- **Method / Path**: `PUT /api/cart/items/{id}`
- **Akses**: login (pemilik item)
- Body: `jumlah` (wajib, min 1).
- `404` bila item bukan milik user; `422` bila melebihi stok / varian tidak tersedia.

### 8.4. Hapus Item
- **Method / Path**: `DELETE /api/cart/items/{id}`
- **Akses**: login (pemilik item)
- `404` bila item bukan milik user.

### 8.5. Kosongkan Keranjang
- **Method / Path**: `DELETE /api/cart`
- **Akses**: login
- **Deskripsi**: Menghapus semua item pada keranjang aktif.

---

## 9. Voucher

### 9.1. Daftar Voucher
- **Method / Path**: `GET /api/vouchers`
- **Akses**: admin
- **Deskripsi**: Semua voucher termasuk `nonaktif`. Paginasi.

Query: `search` (kode/nama), `page`, `per_page` (1-100, default 15).

### 9.2. Tambah Voucher
- **Method / Path**: `POST /api/vouchers`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| kode_voucher | string | ya | unik, maks 50 |
| nama_voucher | string | ya | maks 100 |
| deskripsi | string | tidak | - |
| tipe_diskon | string | ya | `persentase` / `nominal` |
| nilai_diskon | numeric | ya | min 0 |
| minimal_pembelian | numeric | tidak | min 0 (default 0) |
| maksimal_diskon | numeric | tidak | min 0 (batas diskon persentase) |
| kuota_total | integer | tidak | min 0 |
| kuota_per_pengguna | integer | tidak | min 0 (default 1) |
| tanggal_mulai | date | ya | - |
| tanggal_selesai | date | ya | harus >= `tanggal_mulai` |
| status_voucher | string | tidak | `aktif` / `nonaktif` (default `aktif`) |

Respons `201`.

### 9.3. Voucher Tersedia (Pelanggan)
- **Method / Path**: `GET /api/vouchers/available`
- **Akses**: login
- **Deskripsi**: Voucher aktif, dalam rentang tanggal, kuota belum habis, dan belum dipakai user. Urut `minimal_pembelian`.

Query: `subtotal` (opsional) - menyaring voucher dengan `minimal_pembelian <= subtotal`.

### 9.4. Validasi Voucher
- **Method / Path**: `POST /api/vouchers/validate`
- **Akses**: login

Body:

| Field | Tipe | Wajib |
|---|---|---|
| kode_voucher | string | ya |
| subtotal | numeric | ya |

Respons `200`:

```json
{
  "success": true,
  "message": "Voucher valid.",
  "data": {
    "id_voucher": "...",
    "kode_voucher": "BATIKHEMAT",
    "nama_voucher": "Diskon Batik",
    "tipe_diskon": "persentase",
    "nilai_diskon": 10.0,
    "minimal_pembelian": 100000.0,
    "maksimal_diskon": 50000.0,
    "subtotal": 250000.0,
    "jumlah_diskon": 25000.0
  }
}
```

`422` dengan pesan spesifik bila: kode tidak ditemukan, voucher nonaktif, belum berlaku, kedaluwarsa, subtotal kurang dari minimal, atau kuota habis.

Aturan hitung diskon:
- `persentase`: `subtotal * nilai_diskon / 100`, dibatasi `maksimal_diskon` (bila diisi) dan tidak melebihi `subtotal`.
- `nominal`: `min(nilai_diskon, subtotal)`.

### 9.5. Detail Voucher
- **Method / Path**: `GET /api/vouchers/{id}`
- **Akses**: admin
- `404` bila tidak ditemukan.

### 9.6. Ubah Voucher
- **Method / Path**: `PUT /api/vouchers/{id}`
- **Akses**: admin
- Body: field sama seperti tambah, semua opsional (`sometimes`).

### 9.7. Hapus Voucher
- **Method / Path**: `DELETE /api/vouchers/{id}`
- **Akses**: admin
- **Deskripsi**: Soft delete - `status_voucher` menjadi `nonaktif`.

---

## 10. Penggunaan Voucher

### 10.1. Daftar Penggunaan Voucher
- **Method / Path**: `GET /api/voucher-usages`
- **Akses**: admin
- **Deskripsi**: Semua penggunaan voucher beserta voucher dan user. Paginasi (default 15).

Query: `page`, `per_page` (1-100).

### 10.2. Riwayat Voucher Saya
- **Method / Path**: `GET /api/voucher-usages/me`
- **Akses**: login
- **Deskripsi**: Penggunaan voucher milik user yang login, urut terbaru.

---

## 11. Checkout & Pesanan

Alur status pesanan: `menunggu_pembayaran` → `dibayar` → `diproses` → `dikirim` → `selesai`, dengan status tambahan `dibatalkan`.

### 11.1. Checkout
- **Method / Path**: `POST /api/checkout`
- **Akses**: login
- **Deskripsi**: Membuat pesanan dari keranjang `aktif` user. Subtotal diambil dari harga varian saat checkout, stok varian langsung dikurangi, keranjang ditandai `checkout`, dan riwayat status awal `menunggu_pembayaran` dibuat.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_user_address | string | ya | harus alamat milik user yang login |
| kode_voucher | string | tidak | maks 50, divalidasi seperti `POST /api/vouchers/validate` |
| biaya_pengiriman | numeric | tidak | min 0 (default 0) |
| catatan | string | tidak | catatan pesanan |

- `404` bila `id_user_address` bukan milik user.
- `422` bila keranjang kosong, stok tidak mencukupi, varian/produk tidak aktif, atau voucher tidak valid.
- `500` bila terjadi kesalahan saat transaksi (perubahan di-rollback).
- Respons `201`: `data` berisi objek pesanan beserta `items`.

### 11.2. Daftar Pesanan Saya
- **Method / Path**: `GET /api/orders`
- **Akses**: login (hanya milik sendiri)
- **Deskripsi**: Pesanan milik user yang login, urut terbaru. Paginasi.

Query: `status` (salah satu status pesanan), `page`, `per_page` (1-100, default 10).

### 11.3. Detail Pesanan Saya
- **Method / Path**: `GET /api/orders/{id}`
- **Akses**: login (pemilik)
- **Deskripsi**: Detail pesanan beserta item + varian/produk, pembayaran, penggunaan voucher, dan riwayat status.
- `404` bila pesanan tidak ada / bukan milik user.

### 11.4. Daftar Pesanan (Admin)
- **Method / Path**: `GET /api/admin/orders`
- **Akses**: admin
- **Deskripsi**: Semua pesanan beserta item dan user. Paginasi.

Query: `status`, `search` (nomor pesanan / nama penerima), `dari` (tanggal), `sampai` (tanggal), `page`, `per_page` (1-100, default 15).

### 11.5. Detail Pesanan (Admin)
- **Method / Path**: `GET /api/admin/orders/{id}`
- **Akses**: admin
- `404` bila tidak ditemukan.

### 11.6. Ubah Status Pesanan (Admin)
- **Method / Path**: `PUT /api/admin/orders/{id}/status`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| status_pesanan | string | ya | `menunggu_pembayaran` / `dibayar` / `diproses` / `dikirim` / `selesai` / `dibatalkan` |
| catatan | string | tidak | catatan perubahan status |

- Perubahan status dicatat pada `order_status_histories`.
- Ke `dibatalkan` (dari `menunggu_pembayaran`/`dibayar`): stok dikembalikan dan penggunaan voucher dibatalkan.
- Ke `dibayar`: penggunaan voucher berstatus `menunggu` ditandai `digunakan`.
- Respons `200`: `data` objek pesanan terbaru.

---

## 12. Pembayaran (Midtrans Snap)

Pembayaran memakai **Midtrans Snap Sandbox**. Konfigurasi melalui `.env`:

```
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
```

> **Penting**: `MIDTRANS_SERVER_KEY` hanya dipakai di backend dan **tidak boleh** dibagikan ke frontend, log, atau repositori.

### 12.1. Buat / Siapkan Pembayaran
- **Method / Path**: `POST /api/orders/{id}/payment`
- **Akses**: login (pemilik pesanan)
- **Deskripsi**: Membuat transaksi Snap untuk pesanan berstatus `menunggu_pembayaran`. Bila sudah ada pembayaran `menunggu` yang token-nya masih berlaku, token tersebut dipakai ulang.

- `404` bila pesanan tidak ada / bukan milik user.
- `422` bila status pesanan bukan `menunggu_pembayaran`.
- `502` bila gagal membuat transaksi ke Midtrans.
- Respons `201` (transaksi baru) atau `200` (token dipakai ulang).

```json
{
  "success": true,
  "message": "Transaksi pembayaran berhasil dibuat.",
  "data": {
    "id_payment": "...",
    "id_order": "...",
    "nomor_pesanan": "ORD-20261017-0001",
    "provider": "midtrans",
    "status_pembayaran": "menunggu",
    "jumlah_pembayaran": 180000.0,
    "snap_token": "xxxxxxxx",
    "redirect_url": "https://app.sandbox.midtrans.com/snap/v3/redirection/xxxxxxxx",
    "client_key": "SB-Mid-client-xxxxxxxx",
    "expires_at": "2026-10-18T10:00:00.000000Z"
  }
}
```

### 12.2. Detail Pembayaran
- **Method / Path**: `GET /api/orders/{id}/payment`
- **Akses**: login (pemilik pesanan)
- **Deskripsi**: Pembayaran terbaru untuk pesanan.
- `404` bila pesanan tidak ada / bukan milik user atau pembayaran belum dibuat.

### 12.3. Daftar Pembayaran (Admin)
- **Method / Path**: `GET /api/admin/payments`
- **Akses**: admin
- **Deskripsi**: Semua pembayaran beserta pesanan dan user. Paginasi.

Query: `status` (status pembayaran), `provider`, `page`, `per_page` (1-100, default 15).

### 12.4. Webhook Notifikasi Midtrans
- **Method / Path**: `POST /api/payments/midtrans/notification`
- **Akses**: publik (dipanggil server Midtrans; diverifikasi lewat `signature_key`)
- **Deskripsi**: Menerima notifikasi status transaksi, diverifikasi dengan signature `sha512(order_id + status_code + gross_amount + server_key)`, lalu memperbarui `payments` dan `orders`. Setiap notifikasi dicatat pada tabel `webhook_logs`.

Pemetaan status transaksi → pembayaran / pesanan:

| transaction_status | fraud_status | status_pembayaran | status_pesanan |
|---|---|---|---|
| settlement | - | `dibayar` | `dibayar` |
| capture | accept | `dibayar` | `dibayar` |
| capture | challenge | `menunggu` | (tidak berubah) |
| pending | - | `menunggu` | (tidak berubah) |
| deny | - | `ditolak` | `dibatalkan` |
| expire | - | `kedaluwarsa` | `dibatalkan` |
| cancel | - | `dibatalkan` | `dibatalkan` |

- Signature tidak valid → `403`.
- Nominal `gross_amount` tidak cocok dengan `total_pembayaran` → respons `200` dengan `success: false`.
- Notifikasi yang sudah pernah diproses (status pembayaran terminal) diabaikan (idempoten).

---

## 13. Utility

### 13.1. Tes Koneksi
- **Method / Path**: `GET /api/test-json`
- **Akses**: publik
- **Deskripsi**: Endpoint uji cepat. Mengembalikan teks `TEST LARAVEL BERHASIL`.

---

## Ringkasan Cepat

| No | Method | Endpoint | Akses |
|---|---|---|---|
| 1 | POST | /api/register | publik |
| 2 | POST | /api/login | publik |
| 3 | POST | /api/logout | login |
| 4 | GET | /api/me | login |
| 5 | GET | /api/users | admin |
| 6 | GET | /api/addresses | login |
| 7 | POST | /api/addresses | login |
| 8 | GET | /api/addresses/{id} | login |
| 9 | PUT | /api/addresses/{id} | login |
| 10 | DELETE | /api/addresses/{id} | login |
| 11 | GET | /api/categories | publik |
| 12 | GET | /api/categories/{id} | publik |
| 13 | GET | /api/products | publik |
| 14 | GET | /api/products/{id} | publik |
| 15 | POST | /api/products | admin |
| 16 | PUT | /api/products/{id} | admin |
| 17 | DELETE | /api/products/{id} | admin |
| 18 | GET | /api/admin/products | admin |
| 19 | GET | /api/admin/products/{id} | admin |
| 20 | GET | /api/products/{id}/variants | publik |
| 21 | POST | /api/products/{id}/variants | admin |
| 22 | PUT | /api/variants/{id} | admin |
| 23 | DELETE | /api/variants/{id} | admin |
| 24 | GET | /api/products/{id}/images | publik |
| 25 | POST | /api/products/{id}/images | admin |
| 26 | DELETE | /api/images/{id} | admin |
| 27 | GET | /api/wishlist | login |
| 28 | POST | /api/wishlist | login |
| 29 | DELETE | /api/wishlist/{id} | login |
| 30 | GET | /api/cart | login |
| 31 | POST | /api/cart/items | login |
| 32 | PUT | /api/cart/items/{id} | login |
| 33 | DELETE | /api/cart/items/{id} | login |
| 34 | DELETE | /api/cart | login |
| 35 | GET | /api/vouchers | admin |
| 36 | POST | /api/vouchers | admin |
| 37 | GET | /api/vouchers/available | login |
| 38 | POST | /api/vouchers/validate | login |
| 39 | GET | /api/vouchers/{id} | admin |
| 40 | PUT | /api/vouchers/{id} | admin |
| 41 | DELETE | /api/vouchers/{id} | admin |
| 42 | GET | /api/voucher-usages | admin |
| 43 | GET | /api/voucher-usages/me | login |
| 44 | POST | /api/checkout | login |
| 45 | GET | /api/orders | login |
| 46 | GET | /api/orders/{id} | login |
| 47 | POST | /api/orders/{id}/payment | login |
| 48 | GET | /api/orders/{id}/payment | login |
| 49 | GET | /api/admin/orders | admin |
| 50 | GET | /api/admin/orders/{id} | admin |
| 51 | PUT | /api/admin/orders/{id}/status | admin |
| 52 | GET | /api/admin/payments | admin |
| 53 | POST | /api/payments/midtrans/notification | publik |
| 54 | GET | /api/test-json | publik |
