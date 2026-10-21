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
| 502 | Gagal pada layanan pihak ketiga (Midtrans / RajaOngkir) |
| 503 | Layanan pihak ketiga tidak dapat dihubungi (RajaOngkir) |

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
- `auth:sanctum` â€” wajib login
- `admin` â€” wajib login **dan** role `admin` (dicek `EnsureUserIsAdmin`)

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

### PUT `/me`

Perbarui profil pengguna yang sedang login (boleh untuk semua peran). **Body** (semua opsional, minimal satu field):

```json
{
  "nama_pengguna": "string (required jika dikirim, min:2, max:100)",
  "email": "email (unique, diabaikan bila milik sendiri)",
  "nomor_telepon": "string (nullable, max:20)"
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "Profil berhasil diperbarui.",
  "data": { "id_user": "fb6fb6d1-c180-46bc-a85d-6094e18bda86", "nama_pengguna": "Pelanggan Baru", "email": "pelanggan_y6zsvzud5m@example.com", "nomor_telepon": "08129972430", "peran": "pelanggan", "dibuat_pada": "2026-10-19T15:03:23.000000Z", "diperbarui_pada": "2026-10-19T15:03:23.000000Z" }
}
```

**422** bila format salah, nama kosong, atau email sudah dipakai pengguna lain (`"The email has already been taken."`). **401** tanpa token.

### PUT `/me/password`

Ganti kata sandi pengguna yang sedang login. **Body:**

```json
{
  "kata_sandi_saat_ini": "string (required â€” kata sandi lama, salah = 422)",
  "kata_sandi": "string (required, min:8, confirmed)"
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "Kata sandi berhasil diperbarui.",
  "data": { "email": "..." }
}
```

**422** `"Kata sandi saat ini salah."` bila `kata_sandi_saat_ini` tidak cocok (data tidak diubah). Token lama tetap berlaku.

---

## 2. Users (Admin)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/users` | `auth:sanctum` + `admin` | Daftar semua user |
| POST | `/users` | `auth:sanctum` + `admin` | Buat pengguna baru |
| GET | `/users/{id}` | `auth:sanctum` + `admin` | Detail pengguna |
| GET | `/admin/users/summary` | `auth:sanctum` + `admin` | Ringkasan jumlah pengguna |
| GET | `/admin/users/export` | `auth:sanctum` + `admin` | Ekspor CSV seluruh pengguna |

**GET `/users`**

Query: `page`, `per_page` (default 15). Diurutkan by `nama_pengguna`.

Response `data` berisi item paginator + `meta`.

**POST `/users`**

Body:

```json
{
  "nama_pengguna": "required, string, max:80",
  "email": "required, email, max:255, unique:users,email",
  "nomor_telepon": "nullable, string, max:20",
  "kata_sandi": "required, string, min:8, confirmed",
  "peran": "nullable, in:pelanggan,admin"
}
```

- **201** `Pengguna berhasil dibuat.` — `data` berisi profil (hanya kolom profil; `kata_sandi`, `remember_token`, dan token tidak pernah ikut).
- `peran` default `pelanggan` bila tidak dikirim. Kata sandi disimpan ter-hash (`Hash::make`).
- **422** validasi (termasuk `email` duplikat), **401** tanpa token, **403** non-admin.

**GET `/users/{id}`**

- **200** `Detail pengguna berhasil diambil.` — tanpa `kata_sandi`/`remember_token`.
- **404** `Pengguna tidak ditemukan.` (id tidak ada/ tidak valid).

**GET `/admin/users/summary`**

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

Catatan: tabel `users` tidak punya kolom status, sehingga `pengguna_aktif` = `total_pengguna` (semua pengguna dihitung aktif). `per_peran` adalah ringkasan tambahan per peran.

**GET `/admin/users/export`**

- `Content-Type: text/csv; charset=UTF-8`, `Content-Disposition: attachment; filename=pengguna-YYYY-MM-DD.csv`.
- Kolom: `id_user, nama_pengguna, email, nomor_telepon, peran, dibuat_pada`.
- Tanpa paginasi (dibaca per blok 200 baris); tidak memuat kredensial/token.

---

## 3. Kategori

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/categories` | Publik | Daftar kategori |
| GET | `/categories/{id}` | Publik | Detail kategori |
| POST | `/admin/categories` | `admin` | Buat kategori baru |
| PUT | `/admin/categories/{id}` | `admin` | Ubah kategori (parsial) |
| DELETE | `/admin/categories/{id}` | `admin` | Nonaktifkan kategori |

`{id}` = `id_category`. Diurutkan by `nama_kategori`. **404** jika tidak ditemukan.

**POST `/admin/categories`** Body:

```json
{
  "nama_kategori": "required, string, max:80",
  "slug": "nullable, string, max:100, unique:categories,slug",
  "deskripsi": "nullable, string",
  "lokasi_gambar": "nullable, string, max:200",
  "status_aktif": "nullable, boolean (default true)"
}
```

- **201** `Kategori berhasil dibuat.`
- `slug` otomatis di-slugify; jika tidak dikirim, dibuat unik dari `nama_kategori` (mis. `batik-tulis-1`). `nama_kategori` sendiri **tidak** unique di skema.

**PUT `/admin/categories/{id}`**

- Semua field `sometimes` (boleh dikirim sebagian). `slug` yang sudah ada dikecualikan dari aturan unique untuk kategori ini sendiri.
- **200** `Kategori berhasil diperbarui.` / **404** `Kategori tidak ditemukan.`

**DELETE `/admin/categories/{id}`**

- **Bukan hapus fisik**: hanya set `status_aktif = false` sehingga relasi `products.id_category` (FK `ON DELETE RESTRICT`) tidak pernah terputus dan produk tetap aman.
- **200** `Kategori berhasil dinonaktifkan.` (idempoten, baris tetap ada, `data` berisi kategori) / **404** `Kategori tidak ditemukan.`
- Keputusan ini konsisten dengan pola `DELETE` produk/varian/voucher di proyek ini.

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
| GET | `/admin/products/export` | `admin` | Ekspor CSV seluruh produk |
| GET | `/admin/products/{id}` | `admin` | Detail produk (semua varian) |
| POST | `/products` | `admin` | Buat produk |
| PUT | `/products/{id}` | `admin` | Update produk |
| DELETE | `/products/{id}` | `admin` | Nonaktifkan produk |

**Query GET `/admin/products`** — query publik di atas ditambah:

| Param | Tipe | Keterangan |
|---|---|---|
| `status` | `draft \| aktif \| nonaktif` | Filter `status_produk` |
| `sort` | `nama_produk \| dibuat_pada \| status_produk` | Default `dibuat_pada` desc |
| `order` | `asc \| desc` | Default `asc` |

- Relasi `variants` ikut disertakan **hanya** di jalur admin (jalur publik tidak berubah, tanpa `variants`).
- Parameter `status`/`sort`/`order` **tidak divalidasi** di `GET /products` publik (diabaikan), sehingga perilaku publik tidak berubah.

**GET `/admin/products/export`**

- Didapat sebelum wildcard `/{id}` (regresi terhadap konflik rute `export` vs `{id}`).
- `Content-Type: text/csv; charset=UTF-8`, `Content-Disposition: attachment; filename=produk-YYYY-MM-DD.csv`.
- Kolom: `id_product, nama_produk, slug, kategori, status_produk, harga_min, harga_maks, stok_total, jumlah_varian, dibuat_pada`.
- Harga/stok diambil agregat dari `product_variants` (`MIN harga`, `MAX harga`, `SUM stok`, `COUNT varian`).
- Tanpa paginasi (dibaca per blok 200 baris); menerima query `search`, `category`, `status`, `sort`, `order` yang sama dengan daftar admin; `page`/`per_page` tidak berpengaruh.

**POST/PUT `/products` Body:**

```json
{
  "id_category": "string (required, exists:categories,id_category)",
  "nama_produk": "string (required, max:150)",
  "slug": "string (nullable, max:180, unique)",
  "deskripsi": "string (nullable)",
  "sku": "string (nullable, max:100, unique)",
  "berat_produk": "integer (nullable, min:1, max:1000000) - gram",
  "bahan_kain": "string (nullable, max:100)",
  "status_produk": "draft | aktif | nonaktif (nullable)"
}
```

Catatan:
- `slug` otomatis di-slugify. Jika kosong/null saat POST, di-generate unik dari `nama_produk` (mis. `batik-tulis-1`).
- Default `status_produk` saat POST: `draft`.
- `PUT` bersifat parsial: field yang tidak dikirim tidak berubah; field yang dikirim `null` ditulis `null` (kecuali `slug`).
- `status_produk = aktif` mensyaratkan produk sudah punya minimal 1 foto produk (`type=produk`). Buat sebagai `draft`, unggah gambarnya, lalu aktifkan.
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
| POST | `/admin/products/{id}/variants/bulk` | `admin` | Simpan banyak varian sekaligus (SKU unik) |

**POST `/products/{id}/variants` Body:**

```json
{
  "sku": "string (required, max:100, unique)",
  "nama_varian": "string (nullable, max:100)",
  "warna": "string (nullable, max:50)",
  "ukuran": "string (nullable, max:30)",
  "harga": "numeric (required, min:0, max:9999999999.99)",
  "stok": "integer (nullable, min:0, default 0)",
  "berat_gram": "integer (nullable, min:1, max:1000000, default 500)",
  "status_varian": "aktif | nonaktif (nullable, default aktif)"
}
```

**PUT `/variants/{id}` Body:** partial, semua field `sometimes`.
**DELETE** set `status_varian = nonaktif`. Ditolak `422` bila varian tersebut satu-satunya varian `aktif` yang memiliki ukuran pada produk itu.
**404** jika produk/varian tidak ditemukan.

**POST `/admin/products/{id}/variants/bulk` Body:**

```json
{
  "items": [
    { "sku": "string (required, max:100, unique)", "ukuran": "string (nullable, max:30)", "harga": "numeric (required, min:0)", "stok": "integer (nullable, min:0)", "berat_gram": "integer (nullable, min:1, max:1000000)", "warna": "string (nullable, max:50)", "nama_varian": "string (nullable, max:100)", "status_varian": "aktif | nonaktif (nullable, default aktif)" },
    { "id_variant": "uuid (optional, harus milik produk ini)", "stok": "integer (nullable, min:0)" }
  ],
  "hapus_yang_tidak_disediakan": "boolean (nullable, default false)"
}
```

- `items` wajib, 1..500 item.
- Item tanpa `id_variant` = varian baru; item dengan `id_variant` diperbarui parsial.
- SKU duplikat dalam payload / bentrok dengan varian lain → `422`. Varian yang diperbarui boleh tetap memakai SKU-nya sendiri.
- `hapus_yang_tidak_disediakan=true` menonaktifkan varian aktif yang tidak ada di payload.
- Response `200`: `dibuat`, `diperbarui`, `dinonaktifkan`, `items`.

---

## 6. Gambar Produk

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/products/{id}/images` | Publik | Daftar gambar produk (`?type=produk\|size_chart`) |
| POST | `/products/{id}/images` | `admin` | Upload gambar (multipart) |
| DELETE | `/images/{id}` | `admin` | Hapus gambar + file fisik |
| GET | `/admin/products/{id}/images` | `admin` | Daftar gambar (produk draft/nonaktif ikut) |
| POST | `/admin/products/{id}/images` | `admin` | Upload gambar |
| DELETE | `/admin/products/{id}/images/{imageId}` | `admin` | Hapus gambar scoped ke produk tersebut |

**POST `/products/{id}/images` (multipart/form-data):**

| Field | Tipe | Keterangan |
|---|---|---|
| `gambar` | file | required, image, mimes: jpg/jpeg/png/webp, max: 2048 KB |
| `type` | string | nullable, `produk` \| `size_chart` (default `produk`) |
| `id_variant` | string | nullable, harus milik produk ini; WAJIB kosong untuk `size_chart` |
| `alt_text` | string | nullable, max:150 |
| `is_primary` | boolean | nullable, jika true primary lain di-unset; size chart selalu false |
| `urutan` | integer | nullable, min:0; default = max(urutan)+1 per type; jika gambar pertama otomatis primary |

Batas: maksimal 5 foto produk (`type=produk`) per produk, maksimal 1 size chart. Size chart tidak memakai `id_variant`.

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
  "alamat_utama": "boolean (nullable)",
  "id_tujuan_rajaongkir": "integer (nullable, min:1)"
}
```

`id_tujuan_rajaongkir` adalah ID tujuan (per kelurahan) dari `GET /shipping/destinations`, disimpan agar ongkir checkout bisa dihitung backend. Jika `alamat_utama = true`, alamat lain milik user di-unset jadi false. Alamat pertama otomatis jadi utama. Ordering: `alamat_utama` desc, `dibuat_pada` desc. **404** jika tidak ditemukan / bukan milik user.

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
- Varian harus `aktif` **dan** produk harus `aktif` â†’ jika tidak: **422** `"Varian produk tidak tersedia untuk dibeli."`
- Jika `jumlah > stok` â†’ **422** `"Stok tidak mencukupi. Stok tersedia: {stok}."`
- POST merge jumlah existing + baru, total tidak boleh > stok
- **404** item keranjang tidak ditemukan / bukan milik user

Response menyertakan relasi `variant.product`.

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
- Produk harus `aktif` â†’ jika tidak: **404** `"Produk tidak ditemukan atau tidak tersedia."`
- Tidak boleh duplikat â†’ **422** `"Produk sudah ada di wishlist."`

GET menyertakan relasi `product.category`, `product.variants` (aktif), `product.images` (urut). **404** jika item wishlist bukan milik user.

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

Jika valid â†’ **200** dengan info voucher + diskon. Jika tidak valid â†’ **422** dengan pesan:
- `"Kode voucher tidak ditemukan."`
- `"Voucher tidak aktif."`
- `"Voucher belum berlaku."`
- `"Voucher sudah kedaluwarsa."`
- `"Minimal pembelian untuk voucher ini adalah Rp{minimal}."`
- `"Kuota voucher sudah habis atau sudah Anda gunakan."`

**Perhitungan diskon:**
- `persentase` â†’ `subtotal * nilai_diskon / 100`, dibatasi `maksimal_diskon` (jika ada), maksimal `subtotal`
- `nominal` â†’ `min(nilai_diskon, subtotal)`

**Kuota** dihitung dari `voucher_usages` dengan `status_penggunaan IN ('menunggu','digunakan')` â€” cek `kuota_total` dan `kuota_per_pengguna`.

Status penggunaan voucher: `menunggu` | `digunakan` | `dibatalkan`.

### Aksi publikasi voucher (admin)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/vouchers/{id}/publish` | `admin` | Set `status_voucher = aktif` |
| POST | `/vouchers/{id}/unpublish` | `admin` | Set `status_voucher = nonaktif` |
| GET | `/vouchers/summary` | `admin` | Ringkasan seluruh voucher |

**`POST /vouchers/{id}/publish`** â€” idempoten (sudah aktif tetap **200**). **422** `"Voucher sudah kedaluwarsa dan tidak dapat dipublikasikan."` bila `tanggal_selesai < now`. **404** bila voucher tidak ada.

**Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Voucher berhasil dipublikasikan.",
  "data": { "id_voucher": "1cda1f77-32a8-483c-9665-e75b62ef77dc", "kode_voucher": "VCH4WO8GR", "nama_voucher": "Voucher Uji", "deskripsi": "Voucher untuk pengujian", "tipe_diskon": "persentase", "nilai_diskon": "10.00", "minimal_pembelian": "0.00", "maksimal_diskon": null, "kuota_total": null, "kuota_per_pengguna": 1, "tanggal_mulai": "2026-10-18T15:03:13.000000Z", "tanggal_selesai": "2026-10-20T15:03:13.000000Z", "status_voucher": "aktif", "dibuat_pada": "2026-10-19T15:03:13.000000Z", "diperbarui_pada": "2026-10-19T15:03:13.000000Z" }
}
```

**`POST /vouchers/{id}/unpublish`** â€” menarik voucher dari publikasi (`status_voucher = nonaktif`), respons berbentuk sama dengan `message` `"Voucher berhasil ditarik dari publikasi."`.

**`GET /vouchers/summary`** â€” **Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Ringkasan voucher berhasil diambil.",
  "data": { "total_voucher": 5, "aktif": 4, "nonaktif": 1, "berjalan": 2, "terjadwal": 1, "kadaluarsa": 1, "total_penggunaan": 2, "total_diskon_diberikan": 35000 }
}
```

`berjalan` = aktif & `tanggal_mulai <= now <= tanggal_selesai`, `terjadwal` = belum mulai, `kadaluarsa` = sudah lewat `tanggal_selesai`. Semua endpoint di atas: **401** tanpa token, **403** untuk non-admin.

---

## 11. Checkout (Login)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/checkout` | `auth:sanctum` | Buat pesanan dari keranjang aktif |

**Body:**

```json
{
  "id_user_address": "string (required)",
  "kurir": "jne | pos | tiki | sicepat | jnt | anteraja | jxpress | lex | lion | ninja (required)",
  "layanan": "string (required, max:50) â€” kode/nama layanan dari POST /shipping/cost",
  "kode_voucher": "string (nullable, max:50)",
  "biaya_pengiriman": "numeric (nullable, min:0, max:9999999999.99) â€” DIABAIKAN",
  "catatan": "string (nullable)"
}
```

> `biaya_pengiriman` dari klien tidak dipercaya. Ongkir dihitung ulang di backend: `POST /shipping/cost` ke RajaOngkir (`origin` dari `RAJAONGKIR_ORIGIN_ID`, `destination` = `id_tujuan_rajaongkir` alamat, `weight` = Î£(`berat_gram` varian Ã— jumlah), `courier` = `kurir`), lalu biaya diambil dari layanan yang cocok dengan `layanan`.

**Proses:**
1. Validasi alamat milik user â†’ **404** jika tidak ada
2. Ambil keranjang aktif + items â†’ **422** `"Keranjang Anda kosong."` jika kosong
3. Hitung ongkir via RajaOngkir â†’ **422/502/503** jika gagal (lihat di bawah); **422** `ALAMAT_TANPA_TUJUAN_RAJAONGKIR` bila alamat belum punya `id_tujuan_rajaongkir`; **422** `LAYANAN_TIDAK_TERSEDIA` bila `layanan` tidak ada pada hasil kurir
4. **Dalam transaksi DB:** lock setiap variant, cek variant aktif + produk aktif + stok cukup â†’ **422** jika tidak
5. Hitung `subtotal`; jika ada `kode_voucher`, validasi via `VoucherService` â†’ **422** jika tidak valid
6. `total = max(0, subtotal + biaya_pengiriman - diskon)`
7. Buat order (`nomor_pesanan` unik format `ORD-YYYYMMDD-XXXX`, status `menunggu_pembayaran`)
8. Buat `order_items`, kurangi stok variant, catat `inventory_movement` (jenis `keluar`, referensi `nomor_pesanan`)
9. Jika voucher â†’ buat `voucher_usages` status `menunggu`
10. Catat `order_status_history` (null â†’ `menunggu_pembayaran`)
11. Set keranjang `status_cart = checkout`

**Response 201:** order + items.

**Error ongkir** (body `{ "success": false, "message": "<KODE>" }`, pesanan tidak dibuat):

| Status | Kode |
|---|---|
| 422 | `ALAMAT_TANPA_TUJUAN_RAJAONGKIR`, `LAYANAN_TIDAK_TERSEDIA`, `RAJAONGKIR_INVALID_REQUEST`, `RAJAONGKIR_DESTINATION_NOT_FOUND`, `RAJAONGKIR_NO_SERVICE` |
| 502 | `RAJAONGKIR_UPSTREAM_ERROR`, `RAJAONGKIR_UNAUTHORIZED`, `RAJAONGKIR_NOT_CONFIGURED`, `RAJAONGKIR_INVALID_RESPONSE` |
| 503 | `RAJAONGKIR_UNAVAILABLE` |

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
| GET | `/admin/orders/summary` | `admin` | Ringkasan pesanan (diurutkan sebelum `{id}` agar tidak tertangkap wildcard) |
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
- Jika status sama â†’ return order (tanpa history)
- Jika ke `dibatalkan` dari `menunggu_pembayaran`/`dibayar` â†’ **restore stok** (lock variant, `+jumlah`, catat `inventory_movement` jenis `masuk`, referensi `nomor_pesanan`, catatan `"Pengembalian stok pembatalan pesanan"`), dan `voucher_usages` (`menunggu`/`digunakan` â†’ `dibatalkan`)
- Jika ke `dibayar` â†’ `voucher_usages` `menunggu` â†’ `digunakan` + `digunakan_pada`
- Catat `order_status_history` (`status_sebelumnya`, `status_baru`, `catatan`, `diubah_oleh`)
- Update `status_pesanan`

**404** jika pesanan tidak ditemukan / bukan milik user.

**GET `/admin/orders/summary` Query:** `status` (in status pesanan), `dari` & `sampai` (date, filter `dibuat_pada`). **Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Ringkasan pesanan berhasil diambil.",
  "data": { "total_pesanan": 3, "total_nilai": 400000, "rata_rata_nilai": 133333.33, "per_status": { "menunggu_pembayaran": 1, "dibayar": 1, "diproses": 0, "dikirim": 0, "selesai": 0, "dibatalkan": 1 } }
}
```

`total_nilai` = Î£ `total_pembayaran` order; `rata_rata_nilai` dibulatkan 2 desimal (0 bila tidak ada data). **422** untuk `status`/`tanggal` tidak valid; **401**/**403** untuk non-admin.

---

## 13. Pembayaran (Midtrans)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/orders/{id}/payment` | `auth:sanctum` | Buat transaksi pembayaran Midtrans Snap |
| GET | `/orders/{id}/payment` | `auth:sanctum` | Detail pembayaran terakhir untuk order |
| GET | `/admin/payments` | `admin` | Daftar semua pembayaran |
| GET | `/admin/payments/summary` | `admin` | Ringkasan pembayaran |
| POST | `/payments/midtrans/notification` | Publik | Webhook notifikasi Midtrans |

### POST `/orders/{id}/payment`

Syarat: order milik user **dan** `status_pesanan == menunggu_pembayaran` (selain itu **422** `"Pesanan ini tidak dapat dibayar."`).

Logika:
- Jika ada payment terakhir berstatus `menunggu` dengan `payment_reference` dan (`expires_at` null / masih future) â†’ **reuse** (**200**)
- Jika tidak â†’ buat Payment (status `menunggu`), panggil Midtrans Snap. Jika gagal â†’ hapus payment & **502**. Update `payment_reference` (snap token), `payment_url`, `expires_at = now() + 1 hari`. Return **201**.

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
- Config: `serverKey`, `clientKey`, `isProduction`, `isSanitized`, `is3ds`. Jika `curl.cainfo`/`openssl.cafile` kosong â†’ pakai `cacert.pem` bawaan SDK.
- `createSnapTransaction`: `gross_amount = round(total_pembayaran)` (integer). Sertakan `customer_details` (nama penerima, telepon, shipping address, email jika ada).
- `notification()` â†’ objek `Midtrans\Notification` (status authoritative dari Midtrans).
- `verifySignature`: `sha512(order_id + status_code + gross_amount + server_key)`, dicek dengan `hash_equals`.

Konfigurasi di `config/services.php` â†’ env: `MIDTRANS_MERCHANT_ID`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION`.

### GET `/orders/{id}/payment`

Ambil payment terakhir (by `dibuat_pada` desc) untuk order milik user. **404** jika order tidak ada / bukan milik user / belum ada payment.

### GET `/admin/payments`

Query: `status` (status_pembayaran), `provider`, `page`, `per_page` (default 15). Menyertakan relasi `order.user`.

### GET `/admin/payments/summary`

Query: `status` (in `menunggu`|`dibayar`|`ditolak`|`kedaluwarsa`|`dibatalkan`), `provider`, `dari` & `sampai` (date, filter `dibuat_pada`).

**Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Ringkasan pembayaran berhasil diambil.",
  "data": { "total_pembayaran": 4, "total_nilai": 475000, "nilai_dibayar": 250000, "per_status": { "menunggu": 2, "dibayar": 1, "ditolak": 0, "kedaluwarsa": 0, "dibatalkan": 1 }, "per_provider": { "manual": 1, "midtrans": 3 } }
}
```

`nilai_dibayar` = Î£ pembayaran berstatus `dibayar`. **422** untuk nilai filter tidak valid; **401**/**403** untuk non-admin.

### POST `/payments/midtrans/notification` (Webhook)

Endpoint publik (tanpa auth). Dipanggil Midtrans. Verifikasi pakai signature + SDK. Dibatasi per IP (`throttle:midtrans-webhook`, default 60/menit, `MIDTRANS_WEBHOOK_THROTTLE`) â†’ melebihi batas: **429**.

**Alur:**
1. Verifikasi signature via `MidtransService::verifySignature` â†’ jika gagal: catat ke **application log** (bukan DB), **403** `"Signature notifikasi tidak valid."` (tidak menyentuh database)
2. Log ke `webhook_logs` (provider, payload mentah, `signature_key`, IP, endpoint, method)
3. Ambil notification authoritative via `MidtransService::notification()`
4. Cari order by `nomor_pesanan` â†’ jika tidak ada: **200** (ack, tapi log `gagal`)
5. Cocokkan `gross_amount` (2 desimal) dengan `total_pembayaran` order â†’ jika beda: **200** (ack, log `gagal`)
6. Mapping status transaksi Midtrans â†’ status payment + status order:

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

7. Dalam transaksi DB: lock payment terakhir. Jika sudah terminal (`dibayar`/`ditolak`/`kedaluwarsa`/`dibatalkan`) â†’ duplikat, diabaikan (ack). Update payment (`provider_transaction_id`, `metode_pembayaran`, `status_pembayaran`, `paid_at` jika paid). Update order via `OrderService::changeStatus` jika `mapping.order` tidak null & beda.
8. Log status `diproses`/`ganda`, **200**.

Response sukses:
```json
{
  "success": true,
  "message": "Notifikasi berhasil diproses."
}
```

---

## 14. Ongkos Kirim (RajaOngkir)

Integrasi API resmi RajaOngkir (Komerce) `https://rajaongkir.komerce.id/api/v1` (header `key` = `RAJAONGKIR_API_KEY`). Origin toko dibaca dari tabel `settings` (`rajaongkir.origin_id`, bisa diubah admin via `/admin/shipping/config`) dengan fallback env `RAJAONGKIR_ORIGIN_ID`. Daftar kurir yang diterima `courier` = kurir **aktif** menurut konfigurasi admin (lihat Â§16; default semua kurir aktif, identik dengan perilaku lama). Pencarian tujuan di-cache agar hemat kuota.

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/shipping/destinations` | `auth:sanctum` | Cari tujuan pengiriman (per kelurahan) |
| POST | `/shipping/cost` | `auth:sanctum` | Hitung ongkir |

**GET `/shipping/destinations` Query:**

| Param | Tipe | Keterangan |
|---|---|---|
| `search` | string | required, min:2, max:100 (nama kelurahan/kecamatan/kota/kode pos) |

**Response 200:**

```json
{
  "success": true,
  "message": "Daftar tujuan pengiriman berhasil diambil.",
  "data": [
    {
      "id": 61622,
      "label": "BANJARSARI, BANJARSARI, SURAKARTA, JAWA TENGAH, 57136",
      "province_name": "JAWA TENGAH",
      "city_name": "SURAKARTA",
      "district_name": "BANJARSARI",
      "subdistrict_name": "BANJARSARI",
      "zip_code": "57136"
    }
  ]
}
```

Simpan `id` ke `user_addresses.id_tujuan_rajaongkir`. Tidak ada hasil â†’ `200`, `data: []`.

**POST `/shipping/cost` Body:**

```json
{
  "destination": "integer (required, min:1) â€” ID tujuan dari /shipping/destinations",
  "weight": "integer (required, min:1, max:30000) â€” berat total gram",
  "courier": "jne | pos | tiki | sicepat | jnt | anteraja | jxpress | lex | lion | ninja (required)"
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "Biaya pengiriman berhasil dihitung.",
  "data": [
    { "name": "JNE", "code": "jne", "service": "CTC", "description": "Layanan Reguler", "cost": 9000, "etd": "2-3" }
  ]
}
```

**Error** (body `{ "success": false, "message": "<KODE>" }`): `422` `RAJAONGKIR_INVALID_REQUEST` / `RAJAONGKIR_DESTINATION_NOT_FOUND` / `RAJAONGKIR_NO_SERVICE`, `502` `RAJAONGKIR_UPSTREAM_ERROR` / `RAJAONGKIR_UNAUTHORIZED` / `RAJAONGKIR_NOT_CONFIGURED` / `RAJAONGKIR_INVALID_RESPONSE`, `503` `RAJAONGKIR_UNAVAILABLE`.

Ketahanan: request diulang otomatis **1 kali** (delay 250 ms) hanya saat gangguan koneksi atau upstream `5xx`; error `4xx` tidak diulang (hemat kuota). Verifikasi TLS memakai CA sistem; bila kosong, otomatis memakai `cacert.pem` bawaan SDK Midtrans (`RAJAONGKIR_VERIFY` untuk memaksa file tertentu). Cek cepat: `php artisan external:check`.

---

## 15. Pengiriman & Pemenuhan (Admin)

Semua endpoint di grup ini memakai `auth:sanctum` + `admin`: **401** tanpa token, **403** untuk non-admin.

### Pengiriman (tabel `shipments`)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/admin/shipments` | `admin` | Daftar pengiriman |
| PUT | `/admin/shipments/{id}/status` | `admin` | Ubah status pengiriman |

**Status pengiriman:** `menunggu` | `diproses` | `dikirim` | `dalam_perjalanan` | `diterima` | `dibatalkan`.

**Query GET `/admin/shipments`:** `status`, `kurir` (like), `search` (nomor_resi / nomor_pesanan), `dari` & `sampai` (date `dibuat_pada`), `page`, `per_page` (default 15). Menyertakan relasi `order` (`id_order`, `nomor_pesanan`, `status_pesanan`, `nama_penerima`).

**Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Daftar pengiriman berhasil diambil.",
  "data": [
    { "id_shipment": "c92c2906-09cf-48bd-809a-8a35b7995725", "id_order": "7b23ee75-92f7-44b4-a5c6-e7ce20306e9f", "provider": "rajaongkir", "kurir": "JNE", "layanan_pengiriman": "Reguler", "kode_layanan": "REG", "berat_gram": 500, "biaya_pengiriman": "9000.00", "nomor_resi": null, "status_pengiriman": "menunggu", "tanggal_dikirim": null, "tanggal_diterima": null, "dibuat_pada": "2026-10-19T15:03:08.000000Z", "diperbarui_pada": "2026-10-19T15:03:08.000000Z", "order": { "id_order": "7b23ee75-92f7-44b4-a5c6-e7ce20306e9f", "nomor_pesanan": "ORD-SHIP-B", "status_pesanan": "menunggu_pembayaran", "nama_penerima": "Pelanggan 7IJD" } }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 2, "last_page": 1 }
}
```

**PUT `/admin/shipments/{id}/status` Body:**

```json
{
  "status_pengiriman": "menunggu | diproses | dikirim | dalam_perjalanan | diterima | dibatalkan (required)",
  "nomor_resi": "string (nullable, max:100)",
  "lokasi": "string (nullable, max:255)",
  "keterangan": "string (nullable)"
}
```

Aturan bisnis (semua dalam satu transaksi, setiap perubahan dicatat ke `shipment_histories`):
- status sama dengan status saat ini â†’ **422** `"Status pengiriman sama dengan status saat ini."`
- menuju `dikirim`/`dalam_perjalanan` tanpa `nomor_resi` (baru maupun yang sudah ada) â†’ **422** `"Nomor resi wajib diisi sebelum paket dikirim."`
- `dikirim` â†’ `tanggal_dikirim` diisi; `diterima` â†’ `tanggal_diterima` diisi; `nomor_resi` lama dipertahankan bila tidak dikirim ulang
- **404** bila pengiriman tidak ada; **422** `status_pengiriman` di luar daftar

**Response 200 (aktual):** data pengiriman + `histories` + `order`:

```json
{
  "success": true,
  "message": "Status pengiriman berhasil diperbarui.",
  "data": { "id_shipment": "bb656e32-4149-4029-93ce-0d0c1c8ffbac", "id_order": "1b01a253-5e62-4f11-846a-f72f4e555641", "provider": "rajaongkir", "kurir": "JNE", "layanan_pengiriman": "Reguler", "kode_layanan": "REG", "berat_gram": 500, "biaya_pengiriman": "9000.00", "nomor_resi": "RESI-2026-001", "status_pengiriman": "dikirim", "tanggal_dikirim": "2026-10-19T15:03:08.000000Z", "tanggal_diterima": null, "dibuat_pada": "2026-10-19T15:03:08.000000Z", "diperbarui_pada": "2026-10-19T15:03:08.000000Z", "histories": [ { "id_shipment_history": "ac3db4f4-4513-4b25-83f3-e92d467829a1", "id_shipment": "bb656e32-4149-4029-93ce-0d0c1c8ffbac", "status_sebelumnya": "menunggu", "status_baru": "dikirim", "lokasi": "Pekalongan", "keterangan": "Paket diserahkan ke kurir", "waktu_perubahan": "2026-10-19T15:03:08.000000Z", "dibuat_pada": "2026-10-19T15:03:08.000000Z", "diperbarui_pada": "2026-10-19T15:03:08.000000Z" } ], "order": { "id_order": "1b01a253-5e62-4f11-846a-f72f4e555641", "nomor_pesanan": "ORD-SHIP-D", "status_pesanan": "menunggu_pembayaran" } }
}
```

> Tabel `shipments`/`shipment_histories` berasal dari dump database (tidak ada file migrasi baru â€” sengaja mengikuti konvensi repo). Catatan pengujian: urutan dua riwayat yang dibuat dalam detik yang sama tidak dijamin, jadi klien sebaiknya membaca seluruh `histories` (bukan hanya baris pertama).

### Pemenuhan pesanan (tabel `order_fulfillments`, migrasi baru)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/admin/order-fulfillments` | `admin` | Daftar catatan pemenuhan |
| POST | `/admin/orders/{id}/fulfillments` | `admin` | Catat tahap pemenuhan |

**Status pemenuhan:** `persiapan` | `dikemas` | `siap_kirim` | `selesai`.

**Query GET `/admin/order-fulfillments`:** `status`, `id_order`, `page`, `per_page` (default 15). Menyertakan relasi `order` dan `changed_by` (`id_user`, `nama_pengguna`), diurutkan `dibuat_pada` desc lalu `urutan` desc.

**POST `/admin/orders/{id}/fulfillments` Body:**

```json
{ "status_fulfillment": "persiapan | dikemas | siap_kirim | selesai (required)", "catatan": "string (nullable)" }
```

Aturan bisnis:
- pesanan `menunggu_pembayaran`/`dibatalkan`/`selesai` â†’ **422** (pesan: `"Pesanan belum dibayar dan belum dapat dipenuhi."` / `"Pesanan yang dibatalkan tidak dapat dipenuhi."` / `"Pesanan sudah selesai dan tidak perlu dipenuhi lagi."`)
- status sama dengan catatan terakhir â†’ **422** `"Status pemenuhan sama dengan catatan terakhir."`
- setiap catatan mendapat nomor `urutan` menaik per pesanan (dihitung dalam transaksi + lock) sehingga "catatan terakhir" selalu dapat ditentukan
- **404** bila pesanan tidak ada; **422** bila `status_fulfillment` di luar daftar

**Response 201 (aktual):**

```json
{
  "success": true,
  "message": "Pemenuhan pesanan berhasil dicatat.",
  "data": { "id_fulfillment": "897a2e90-60c5-4d3d-971e-9ce0034b2a5c", "id_order": "b2d0fb6b-5613-46e1-8aa0-675e97c49347", "status_fulfillment": "persiapan", "catatan": "Barang disiapkan dari gudang", "diubah_oleh": "947bf6bb-4a4d-4d28-8026-6e72a8f4e059", "urutan": 1, "diperbarui_pada": "2026-10-19T15:03:04.000000Z", "dibuat_pada": "2026-10-19T15:03:04.000000Z", "order": { "id_order": "b2d0fb6b-5613-46e1-8aa0-675e97c49347", "nomor_pesanan": "ORD-FUL-B", "status_pesanan": "dibayar" } }
}
```

---

## 16. Konfigurasi RajaOngkir (Admin)

Semua endpoint `auth:sanctum` + `admin` (**401**/**403** untuk non-admin). Sumber konfigurasi: tabel `settings` (kunci `rajaongkir.origin_id`, `rajaongkir.kurir_aktif` JSON) menang, fallback `config/services` + env `RAJAONGKIR_ORIGIN_ID`. API key **tidak pernah** dikirim balik ke klien (hanya `api_key_terisi: true/false`).

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| GET | `/admin/shipping/config` | `admin` | Baca origin & kurir aktif |
| PUT | `/admin/shipping/config` | `admin` | Ubah `origin_id` (tersimpan ke `settings`) |
| GET | `/admin/shipping/health` | `admin` | Cek konfigurasi + konektivitas (tanpa memakai kuota API) |
| GET | `/admin/shipping/couriers` | `admin` | Daftar kurir + status aktif |
| PUT | `/admin/shipping/couriers/{code}` | `admin` | Aktif/nonaktifkan satu kurir |
| POST | `/admin/shipping/simulate` | `admin` | Cek ongkir multi-kurir (MENGGUNAKAN kuota RajaOngkir) |

**GET `/admin/shipping/config` Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Konfigurasi pengiriman berhasil diambil.",
  "data": { "origin_id": "82935", "sumber_origin": "env", "base_url": "https://rajaongkir.komerce.id/api/v1", "api_key_terisi": true, "kurir_aktif": ["jne", "pos", "tiki", "sicepat", "jnt", "anteraja", "jxpress", "lex", "lion", "ninja"] }
}
```

`sumber_origin` = `settings` bila admin pernah mengubah origin, selain itu `env`.

**PUT `/admin/shipping/config` Body:** `{ "origin_id": "string (required, digits, max:20)" }`. **Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Konfigurasi pengiriman berhasil diperbarui.",
  "data": { "origin_id": "99999", "sumber_origin": "settings", "base_url": "https://rajaongkir.komerce.id/api/v1", "api_key_terisi": true, "kurir_aktif": ["jne", "pos", "tiki", "sicepat", "jnt", "anteraja", "jxpress", "lex", "lion", "ninja"] }
}
```

Perubahan langsung dipakai `POST /shipping/cost`, checkout, dan simulasi (cache `settings:shipping.*` di-flush saat menulis). **422** bila `origin_id` bukan angka/kosong/terlalu panjang (tidak ada yang ditulis).

**GET `/admin/shipping/health` Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Kesehatan konfigurasi pengiriman berhasil diperiksa.",
  "data": { "status": "ok", "checks": { "api_key_terisi": true, "base_url_valid": true, "origin_terisi": true, "konektivitas_https": true }, "base_url": "https://rajaongkir.komerce.id/api/v1", "origin_id": "82935", "jumlah_kurir_aktif": 10, "memakai_kuota_api": false }
}
```

`status`: `belum_terkonfigurasi` (API key/origin kosong) | `degraded` (konektivitas gagal) | `ok`. Hanya 1 request ringan (cek koneksi host) â€” **tidak memanggil endpoint API RajaOngkir** sehingga `memakai_kuota_api` selalu `false`.

**GET `/admin/shipping/couriers` Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Daftar kurir berhasil diambil.",
  "data": [ { "kode": "jne", "aktif": true }, { "kode": "pos", "aktif": true }, { "kode": "tiki", "aktif": true }, { "kode": "sicepat", "aktif": true }, { "kode": "jnt", "aktif": true }, { "kode": "anteraja", "aktif": true }, { "kode": "jxpress", "aktif": true }, { "kode": "lex", "aktif": true }, { "kode": "lion", "aktif": true }, { "kode": "ninja", "aktif": true } ],
  "meta": { "total": 10, "aktif": 10 }
}
```

**PUT `/admin/shipping/couriers/{code}` Body:** `{ "aktif": "boolean (required)" }`. **Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Status kurir berhasil diperbarui.",
  "data": { "kode": "pos", "aktif": false, "kurir_aktif": ["jne", "tiki", "sicepat", "jnt", "anteraja", "jxpress", "lex", "lion", "ninja"] }
}
```

Aturan: kode di luar 10 kurir didukung â†’ **404** `"Kurir tidak ditemukan."`; menonaktifkan kurir terakhir yang aktif â†’ **422** `"Minimal satu kurir harus tetap aktif."`. Kurir nonaktif langsung ditolak `POST /shipping/cost` dan checkout (**422**, pesan validasi `The selected courier is invalid.`).

**POST `/admin/shipping/simulate` Body:**

```json
{
  "destination": "integer (required, min:1)",
  "weight": "integer (required, min:1, max:30000)",
  "couriers": ["jne", "sicepat"] (opsional â€” harus kurir aktif; tanpa ini memakai SEMUA kurir aktif)"
}
```

**Response 200 (aktual):**

```json
{
  "success": true,
  "message": "Simulasi ongkos kirim selesai.",
  "data": { "origin_id": "82935", "destination": 61622, "weight": 1000, "memakai_kuota_api": 2, "hasil": [ { "kurir": "jne", "berhasil": true, "error": null, "layanan": [ { "name": "JNE", "code": "jne", "service": "REG", "description": "Layanan Reguler", "cost": 9000, "etd": "2-3" } ] }, { "kurir": "sicepat", "berhasil": true, "error": null, "layanan": [ { "name": "SICEPAT", "code": "sicepat", "service": "REG", "description": "Layanan Reguler", "cost": 9000, "etd": "2-3" } ] } ] }
}
```

Aturan: upstream error per kurir tidak menggagalkan seluruh permintaan â€” entri jadi `berhasil: false` + `error: "RAJAONGKIR_UPSTREAM_ERROR"` + `layanan: []` (atau pesan error spesifik), sementara kurir lain tetap dihitung. `memakai_kuota_api` = jumlah panggilan API yang benar-benar dilakukan. Tanpa origin â†’ **502** `RAJAONGKIR_NOT_CONFIGURED` tanpa panggilan API sama sekali. Kurir nonaktif yang diminta â†’ **422** `"Tidak ada kurir aktif yang diminta."`. Validasi input (`destination`/`weight`/`couriers`) â†’ **422**.

---

## Catatan Teknis

- ID semua entitas berupa **UUID string** (`id_user`, `id_product`, `id_variant`, `id_order`, dll).
- Timestamp kolom: `dibuat_pada` (created_at), `diperbarui_pada` (updated_at).
- Field bahasa Indonesia: `nama_pengguna`, `kata_sandi`, `peran`, `status_produk`, `status_varian`, `status_pesanan`, `status_pembayaran`, `status_voucher`, `status_penggunaan`, `status_cart`, dll.
- Diskon/voucher: `tipe_diskon` âˆˆ `persentase`/`nominal`.
- Pembayaran via Midtrans Snap. Webhook publik selalu diverifikasi signature (gagal â†’ 403, hanya dicatat ke application log) dan dibatasi per IP (`throttle:midtrans-webhook`) agar tahan banjir request.
- Ongkir via RajaOngkir (`RAJAONGKIR_API_KEY`, `RAJAONGKIR_BASE_URL`, `RAJAONGKIR_ORIGIN_ID` di `.env`); berat per varian dari `product_variants.berat_gram`; tujuan dari `user_addresses.id_tujuan_rajaongkir`.
- Endpoint `/test-json` hanya untuk health check cepat, return teks `"TEST LARAVEL BERHASIL"`.
