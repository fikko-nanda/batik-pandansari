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
| 21 | GET | `/addresses` | Login (milik sendiri) |
| 22 | POST | `/addresses` | Login (milik sendiri) |
| 23 | GET | `/addresses/{id}` | Login (milik sendiri) |
| 24 | PUT | `/addresses/{id}` | Login (milik sendiri) |
| 25 | DELETE | `/addresses/{id}` | Login (milik sendiri) |

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
- GET `/user` (admin) → sama seperti `/me`, tetapi hanya bisa diakses admin.
- GET `/test-json` (publik) → `"TEST LARAVEL BERHASIL"` (cek server hidup).

---

## 7–8. Kategori

### GET `/categories` (publik)

Daftar kategori. Data uji: `Batik Tulis`, `Batik Cap`, `Batik Modern`.

### GET `/categories/{id}` (publik)

`{id}` = `id_category`. Tidak ada → `404`.

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
- Opsional: `slug` (unik, maks 180; auto dari nama bila kosong), `deskripsi`, `status_produk` (`draft/aktif/nonaktif`, default `draft`).
- Respons `201`.

### PUT `/products/{id}` (admin)

Parsial (semua opsional). `slug` unik kecuali dirinya. `404` bila tak ada.

### DELETE `/products/{id}` (admin)

Soft delete → `status_produk='nonaktif'`. `404` bila tak ada.

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

Data uji varian: `3b7c252a-c898-11f1-8b52-002b6777bbb3` (SKU `PARANG-3b7c252a`).

---

## 18–20. Gambar produk

### GET `/products/{id}/images` (publik)

`{id}` = `id_product`; hanya produk `aktif`. Urut `is_primary` desc, `urutan` asc. Tiap item punya `url`.

### POST `/products/{id}/images` (admin) — `multipart/form-data`

| Field | Tipe | Wajib |
|-------|------|-------|
| `gambar` | file jpg/jpeg/png/webp, maks 2MB | ya |
| `alt_text` | teks ≤150 | tidak |
| `is_primary` | boolean | tidak |
| `urutan` | integer ≥0 | tidak |

File disimpan ke disk `public` folder `products/`; `path_gambar` = path relatif.
Gambar pertama otomatis primary; `is_primary=true` menonaktifkan primary lain.
Respons `201` + `url` (mis. `http://localhost:8000/storage/products/xxx.png`).

### DELETE `/images/{id}` (admin)

`{id}` = `id_image`. Hapus baris + file fisik. `404` bila tak ada.

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

## ID siap pakai (data uji)

- Kategori `Batik Cap`: `9f5d8990-c871-11f1-8a96-002b6777bbb3`
- Produk `Batik Parang Klasik`: `3b7676c0-c898-11f1-8b52-002b6777bbb3`
- Produk `Batik Kawung Elegan`: `3b78a908-c898-11f1-8b52-002b6777bbb3`
- Varian `PARANG-3b7c252a`: `3b7c252a-c898-11f1-8b52-002b6777bbb3`
- Gambar `batik-parang.jpg`: `3b844d0c-c898-11f1-8b52-002b6777bbb3`
- Alamat milik `nabila@example.com`: `660e8400-e29b-41d4-a716-446655440000`
