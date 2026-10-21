# Daftar API Backend Pandansari

Dokumentasi seluruh endpoint API yang tersedia pada project `backend-pandansari` (Laravel).

Total: **149 endpoint** (sumber kebenaran: `php artisan route:list --path=api`).

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

Validasi gagal (422) memakai renderer kustom pada `bootstrap/app.php`:

```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
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
| 502 | Gagal pada layanan pihak ketiga (Midtrans / RajaOngkir) |
| 503 | Layanan pihak ketiga tidak dapat dihubungi (RajaOngkir)

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

Respons `201`: `data` berisi objek user, ditambah flag `otp_sent` (boolean).

Catatan: setelah registrasi, kode OTP verifikasi 6 digit dikirim ke email.
Akun baru **belum terverifikasi** sampai OTP dikonfirmasi lewat
`POST /api/otp/verify-registration` (1.14). Bila pengiriman email gagal,
`otp_sent` bernilai `false` dan pengguna dapat meminta kirim ulang lewat
1.15. Login dengan akun yang belum diverifikasi tetap memerlukan kata sandi.

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

### 1.5. Perbarui Profil
- **Method / Path**: `PUT /api/me`
- **Akses**: login (boleh semua peran, hanya untuk diri sendiri)
- **Deskripsi**: Memperbarui `nama_pengguna`, `email`, dan/atau `nomor_telepon`.

Body (semua opsional; `nama_pengguna` bila dikirim wajib 2-100 karakter):

```json
{ "nama_pengguna": "Pelanggan Baru", "email": "baru@example.com", "nomor_telepon": "081299999999" }
```

- `422` bila format email salah, nama kosong, atau email sudah dipakai pengguna lain (`The email has already been taken.`).
- `401` tanpa token.

Respons `200`: `{ "success": true, "message": "Profil berhasil diperbarui.", "data": { "id_user": "...", "nama_pengguna": "Pelanggan Baru", "email": "pelanggan_y6zsvzud5m@example.com", "nomor_telepon": "08129972430", "peran": "pelanggan", "dibuat_pada": "...", "diperbarui_pada": "..." } }`

### 1.6. Ganti Kata Sandi
- **Method / Path**: `PUT /api/me/password`
- **Akses**: login
- **Deskripsi**: Mengganti kata sandi pengguna yang sedang login.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| kata_sandi_saat_ini | string | ya | kata sandi lama |
| kata_sandi | string | ya | kata sandi baru, min 8 karakter |
| kata_sandi_confirmation | string | ya | harus sama dengan `kata_sandi` |

- `422` `"Kata sandi saat ini salah."` bila sandi lama tidak cocok (data tidak diubah).
- Respons `200`: `{ "success": true, "message": "Kata sandi berhasil diperbarui.", "data": { "email": "..." } }` — token lama tetap berlaku.

### 1.7. Daftar Pengguna
- **Method / Path**: `GET /api/users`
- **Akses**: admin
- **Deskripsi**: Menampilkan semua pengguna (paginasi 15 per halaman).

Respons `200`: `data` berisi objek paginator (termasuk `data`, `current_page`, `total`, dll).

### 1.8. Tambah Pengguna
- **Method / Path**: `POST /api/users`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| nama_pengguna | string | ya | maks 80 |
| email | string | ya | format email, unik |
| nomor_telepon | string | tidak | maks 20 |
| kata_sandi | string | ya | min 8, harus sama dengan `kata_sandi_confirmation` |
| peran | string | tidak | `pelanggan` / `admin` (default `pelanggan`) |

- Respons `201`: `{ "success": true, "message": "Pengguna berhasil dibuat.", "data": { ...profil } }`.
- `data` tidak pernah memuat `kata_sandi`, `remember_token`, atau token. Sandi disimpan ter-hash.
- `422` bila validasi gagal (termasuk email duplikat); `401` tanpa token; `403` bukan admin.

### 1.9. Detail Pengguna
- **Method / Path**: `GET /api/users/{id}`
- **Akses**: admin
- **Deskripsi**: Detail satu pengguna berdasarkan `id_user`.
- `404` `"Pengguna tidak ditemukan."` bila id tidak ada/tidak valid.

### 1.10. Ringkasan Pengguna
- **Method / Path**: `GET /api/admin/users/summary`
- **Akses**: admin

Respons `200`:

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

- Tabel `users` tidak memiliki kolom status, sehingga `pengguna_aktif` = `total_pengguna`.
- `per_peran` adalah ringkasan tambahan jumlah pengguna per peran.

### 1.11. Ekspor Pengguna
- **Method / Path**: `GET /api/admin/users/export`
- **Akses**: admin
- **Deskripsi**: Unduh seluruh pengguna sebagai CSV (`text/csv; charset=UTF-8`).

Header unduhan: `attachment; filename=pengguna-YYYY-MM-DD.csv`.

Kolom CSV: `id_user, nama_pengguna, email, nomor_telepon, peran, dibuat_pada`.

Tanpa paginasi (dibaca per blok 200 baris) dan tidak memuat kredensial/token.

### 1.12. Login / Daftar via Google
- **Method / Path**: `POST /api/auth/google`
- **Akses**: publik
- **Deskripsi**: Login atau mendaftar otomatis memakai **ID Token Google** (Sign-In with Google). Token diverifikasi terhadap JWKS resmi Google; `aud` harus cocok dengan `GOOGLE_CLIENT_ID`.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_token | string | ya | ID Token dari flux Google Sign-In (frontend) |

Perilaku:

- **Pengguna baru** (email belum ada): akun dibuat otomatis dengan `peran=pelanggan`, `email_verified_at=now()`, `google_id=sub`, `avatar=picture`, tanpa kata sandi.
- **Email sudah terdaftar & sudah terverifikasi** (`email_verified_at terisi`): akun lama **otomatis ditautkan** (`google_id`/`avatar` diisi), lalu login berhasil. Tidak ada akun baru.
- **Email sudah terdaftar tapi belum terverifikasi** (akun lama dibuat via `/api/register` tapi OTP belum dikonfirmasi): `409` + `requires_linking: true`. Wajib login pakai kata sandi dulu, lalu tautkan Google dari `/api/me/google` — mencegah pengambilalihan akun lewat email yang belum diverifikasi.
- **Email sudah tertaut ke `google_id` lain**: `409`.
- Klaim `peran`/`role` di dalam token **diabaikan**; peran selalu `pelanggan`.

Respons `200` (sama persis dengan `/api/login`):

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": { "token": "1|xxxx", "token_type": "Bearer", "user": {} }
}
```

Kesalahan:

| Code | Kapan |
|---|---|
| 422 | `id_token` tidak dikirim, atau email Google belum terverifikasi |
| 401 | ID Token tidak valid / sudah kedaluwarsa |
| 409 | Email terdaftar tapi belum terverifikasi (`requires_linking: true`), atau email sudah tertaut ke `google_id` lain |
| 502 | Layanan Google tidak dapat dihubungi |
| 503 | `GOOGLE_CLIENT_ID` belum diisi di server |

Dibatasi 20 request/menit per IP (`throttle:google-auth`).

### 1.13. Tautkan Akun Google
- **Method / Path**: `POST /api/me/google`
- **Akses**: login
- **Deskripsi**: Menautkan akun Google ke akun yang sedang login (untuk akun lama yang menerima `requires_linking` saat mencoba login Google).

Body: `id_token` (wajib) — ID Token Google.

Perilaku: `google_id`/`avatar` akun login diisi dari token. Jika akun sudah punya tautan Google lain, tautan lama **diganti**. `google_id` yang sudah dipakai pengguna lain akan `409`.

Respons `200`: `{ "success": true, "message": "Akun Google berhasil ditautkan.", "data": { ...profil } }`

Kesalahan: sama dengan 1.12 (`401` token tidak valid, `422` id_token kosong/email belum terverifikasi, `409` sudah dipakai pengguna lain, `502`/`503`).

### 1.14. Verifikasi OTP Registrasi
- **Method / Path**: `POST /api/otp/verify-registration`
- **Akses**: publik
- **Deskripsi**: Mengonfirmasi kode OTP yang dikirim saat `POST /api/register`. Bila
  berhasil, `users.email_verified_at` diisi sehingga akun dianggap terverifikasi.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| email | string | ya | email terdaftar, maks 150 |
| kode_otp | string | tidak | 6 digit; boleh dikosongkan bila `otp` dikirim |
| otp | string | tidak | alias dari `kode_otp` |

Respons `200`:

```json
{
  "success": true,
  "message": "Verifikasi email berhasil."
}
```

| Code | Kapan |
|---|---|
| 422 | `email`/`kode_otp` tidak valid, OTP salah, kedaluwarsa, atau percobaan habis (maks 5) |
| 429 | Terlalu sering memverifikasi (rate limit `otp-verify`) |

### 1.15. Kirim Ulang OTP Registrasi
- **Method / Path**: `POST /api/otp/resend-registration`
- **Akses**: publik
- **Deskripsi**: Mengirim ulang kode OTP verifikasi registrasi ke email yang sama.
  OTP lama otomatis tidak berlaku (di-invalidate) ketika yang baru dibuat.

Body: `email` (wajib, maks 150).

Respons `200`: `{ "success": true, "message": "Kode OTP verifikasi telah dikirim ulang ke email kamu.", "data": { "expires_in_minutes": 5 } }`

| Code | Kapan |
|---|---|
| 200 | Berhasil; bila email sudah terverifikasi, pesan menyesuaikan dan OTP tidak dikirim ulang |
| 404 | Email tidak terdaftar |
| 422 | `email` tidak valid |
| 429 | Cooldown 300 detik belum habis (`retry_after` dalam detik) atau rate limit `otp-send` |
| 500 | Gagal mengirim email OTP |

### 1.16. Minta OTP Lupa Password
- **Method / Path**: `POST /api/forgot-password`
- **Akses**: publik
- **Deskripsi**: Meminta kode OTP pemulihan password. Demi keamanan, respons
  selalu sama baik email terdaftar maupun tidak (tidak membocorkan keberadaan akun).

Body: `email` (wajib, maks 150).

Respons `200`: `{ "success": true, "message": "Jika email terdaftar, kami telah mengirim kode OTP pemulihan password ke email tersebut." }`

| Code | Kapan |
|---|---|
| 422 | `email` tidak valid |
| 429 | Cooldown 300 detik belum habis (`retry_after` dalam detik) atau rate limit `otp-send` |
| 500 | Gagal mengirim email OTP |

### 1.17. Verifikasi OTP Lupa Password
- **Method / Path**: `POST /api/forgot-password/verify-otp`
- **Akses**: publik
- **Deskripsi**: Memverifikasi kode OTP pemulihan password. Bila berhasil,
  server menerbitkan `reset_token` singkat yang dipakai pada 1.18.

Body: `email` (wajib), `kode_otp` (6 digit, boleh dikosongkan bila `otp` dikirim).

Respons `200`:

```json
{
  "success": true,
  "message": "Kode OTP berhasil diverifikasi.",
  "data": { "reset_token": "token-acak-64-karakter" }
}
```

| Code | Kapan |
|---|---|
| 422 | OTP salah, kedaluwarsa (5 menit), atau percobaan habis (maks 5) |
| 429 | Rate limit `otp-verify` |

### 1.18. Reset Password
- **Method / Path**: `POST /api/forgot-password/reset`
- **Akses**: publik
- **Deskripsi**: Mengatur kata sandi baru memakai `reset_token` dari 1.18. Setelah
  berhasil, seluruh token Sanctum pengguna dicabut (logout paksa di semua perangkat).

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| email | string | ya | email pemilik reset token |
| reset_token | string | tidak | dari 1.17; boleh dikosongkan bila `token` dikirim |
| token | string | tidak | alias dari `reset_token` |
| kata_sandi | string | tidak | min 8 karakter + konfirmasi; boleh dikosongkan bila `password` dikirim |
| password | string | tidak | alias dari `kata_sandi` |

Respons `200`: `{ "success": true, "message": "Password berhasil direset." }`

| Code | Kapan |
|---|---|
| 404 | Email tidak terdaftar |
| 422 | `reset_token`/password kosong, konfirmasi tidak cocok, atau token tidak valid/sudah dipakai |
| 429 | Rate limit `otp-verify` |

### 1.19. Kirim Ulang OTP Lupa Password
- **Method / Path**: `POST /api/forgot-password/resend-otp`
- **Akses**: publik
- **Deskripsi**: Mengirim ulang kode OTP pemulihan password. OTP lama di-invalidate.

Body: `email` (wajib, maks 150).

Respons `200`: `{ "success": true, "message": "Kode OTP pemulihan password telah dikirim ulang ke email kamu.", "data": { "expires_in_minutes": 5 } }`

| Code | Kapan |
|---|---|
| 200 | Berhasil; bila email tidak terdaftar, pesan tetap generik (tidak membocorkan akun) |
| 422 | `email` tidak valid |
| 429 | Cooldown 300 detik belum habis (`retry_after`) atau rate limit `otp-send` |
| 500 | Gagal mengirim email OTP |

> Catatan umum: OTP berlaku **5 menit** (`OTP_TTL_MINUTES`), maksimum **5 percobaan**
> verifikasi, dan pengiriman ulang memiliki cooldown **300 detik**. Batas laju:
> `otp-send` 5 permintaan/menit per email + 30/menit per IP, `otp-verify`
> 10 permintaan/menit per email + 60/menit per IP.

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
| id_tujuan_rajaongkir | integer | tidak | ID tujuan (per kelurahan) dari `GET /api/shipping/destinations`; dipakai saat menghitung ongkir checkout |

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

### 3.3. Tambah Kategori
- **Method / Path**: `POST /api/admin/categories`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| nama_kategori | string | ya | maks 80 (tidak unik) |
| slug | string | tidak | maks 100, unik; otomatis di-slugify & dibuat unik bila kosong |
| deskripsi | string | tidak | - |
| lokasi_gambar | string | tidak | maks 200 |
| status_aktif | boolean | tidak | default `true` |

Respons `201`: `"Kategori berhasil dibuat."`, `data` objek kategori.

### 3.4. Ubah Kategori
- **Method / Path**: `PUT /api/admin/categories/{id}`
- **Akses**: admin
- Semua field opsional (`sometimes`); validasi sama, `slug` unik mengecualikan kategori ini sendiri.
- `200` `"Kategori berhasil diperbarui."`; `404` bila tidak ditemukan.

### 3.5. Nonaktifkan Kategori
- **Method / Path**: `DELETE /api/admin/categories/{id}`
- **Akses**: admin
- **Deskripsi**: **Bukan hapus fisik** — mengubah `status_aktif` menjadi `false`; baris tetap ada dan relasi produk aman (FK `ON DELETE RESTRICT`).
- `200` `"Kategori berhasil dinonaktifkan."` (idempoten); `404` bila tidak ditemukan.

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
- Galeri `images` hanya berisi foto produk (`type = 'produk'`); size chart berada pada field terpisah `size_chart`.

### 4.3. Tambah Produk
- **Method / Path**: `POST /api/products`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_category | string | ya | harus ada di tabel kategori |
| nama_produk | string | ya | maks 150 |
| sku | string | tidak | unik, maks 100 |
| slug | string | tidak | unik, maks 180; dibuat otomatis bila kosong |
| deskripsi | string | tidak | - |
| berat_produk | integer | tidak | 1-1000000 gram |
| bahan_kain | string | tidak | maks 100 |
| status_produk | string | tidak | `draft` / `aktif` / `nonaktif` (default `draft`) |

Respons `201`.

Catatan: `status_produk = 'aktif'` hanya bisa dipakai bila produk sudah punya minimal 1 foto produk. Praktisnya, buat produk sebagai `draft`, unggah gambarnya, lalu aktifkan lewat `PUT`.

### 4.4. Ubah Produk
- **Method / Path**: `PUT /api/products/{id}`
- **Akses**: admin
- Body: field sama, semua opsional.
- Sifat parsial: field yang tidak dikirim tidak berubah; field yang dikirim `null` ditulis `null` (kecuali `slug` yang tidak boleh `null`).
- Perpindahan status ke `aktif` mensyaratkan produk sudah punya minimal 1 foto produk.

### 4.5. Hapus Produk
- **Method / Path**: `DELETE /api/products/{id}`
- **Akses**: admin
- **Deskripsi**: Soft delete - mengubah `status_produk` menjadi `nonaktif`.

### 4.6. Daftar Produk (Admin)
- **Method / Path**: `GET /api/admin/products`
- **Akses**: admin
- **Deskripsi**: Semua produk (termasuk `draft`/`nonaktif`), menyertakan relasi `variants`.

Query publik (`search`, `category`, `page`, `per_page`) ditambah:

| Param | Tipe | Keterangan |
|---|---|---|
| status | string | `draft` / `aktif` / `nonaktif` |
| sort | string | `nama_produk` / `dibuat_pada` / `status_produk` (default `dibuat_pada`) |
| order | string | `asc` / `desc` (default `asc`) |

Parameter khusus admin hanya divalidasi di jalur ini; `GET /api/products` publik tidak berubah.

### 4.7. Detail Produk (Admin)
- **Method / Path**: `GET /api/admin/products/{id}`
- **Akses**: admin
- **Deskripsi**: Semua varian (termasuk `nonaktif`).

### 4.8. Ekspor Produk
- **Method / Path**: `GET /api/admin/products/export`
- **Akses**: admin
- **Deskripsi**: Unduh seluruh produk sebagai CSV (`text/csv; charset=UTF-8`), header `attachment; filename=produk-YYYY-MM-DD.csv`.

Kolom CSV: `id_product, nama_produk, slug, kategori, status_produk, harga_min, harga_maks, stok_total, jumlah_varian, dibuat_pada`.

- Harga & stok adalah agregat dari `product_variants` (`MIN`/`MAX` harga, `SUM` stok, `COUNT` varian).
- Menerima filter/urutan yang sama dengan daftar admin (`search`, `category`, `status`, `sort`, `order`); tanpa paginasi.
- Rute `export` didaftarkan sebelum `/{id}` agar tidak tertangkap wildcard.

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
| berat_gram | integer | tidak | 1-1000000 gram (default 500); dipakai menghitung total berat ongkir |
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
- Ditolak `422` bila varian tersebut satu-satunya varian `aktif` yang memiliki ukuran pada produk itu.

### 5.5. Simpan Banyak Varian (Bulk)
- **Method / Path**: `POST /api/admin/products/{id}/variants/bulk`
- **Akses**: admin
- **Deskripsi**: Menyimpan banyak varian sekaligus untuk satu produk.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| items | array | ya | 1-500 item |
| items.*.id_variant | string | tidak | bila ada harus milik produk ini; tanpa id = varian baru |
| items.*.sku | string | ya | unik, maks 100 |
| items.*.nama_varian | string | tidak | maks 100 |
| items.*.warna | string | tidak | maks 50 |
| items.*.ukuran | string | tidak | maks 30; wajib terisi bila produk memakai ukuran |
| items.*.harga | numeric | ya | min 0 |
| items.*.stok | integer | tidak | min 0 (default 0) |
| items.*.berat_gram | integer | tidak | 1-1000000 gram (default 500) |
| items.*.status_varian | string | tidak | `aktif` / `nonaktif` (default `aktif`) |
| hapus_yang_tidak_disediakan | boolean | tidak | default `false`; `true` menonaktifkan varian aktif yang tidak ada di payload |

- SKU wajib unik: duplikat di payload maupun bentrok dengan varian lain ditolak `422`. Varian yang diperbarui boleh tetap memakai SKU-nya sendiri.
- Item dengan `id_variant` diperbarui secara parsial (hanya field yang dikirim).
- Respons `200` berisi `dibuat`, `diperbarui`, `dinonaktifkan`, dan `items`.

---

## 6. Gambar Produk

### 6.1. Daftar Gambar
- **Method / Path**: `GET /api/products/{id}/images`
- **Akses**: publik
- **Deskripsi**: Gambar produk `aktif`, urut gambar utama lalu `urutan`. Setiap item memiliki `url`.
- Query opsional `?type=produk|size_chart` untuk memfilter jenis gambar.

### 6.2. Unggah Gambar
- **Method / Path**: `POST /api/products/{id}/images`
- **Akses**: admin
- **Content-Type**: `multipart/form-data`

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| gambar | file | ya | jpg/jpeg/png/webp, maks 2048 KB |
| type | string | tidak | `produk` / `size_chart` (default `produk`) |
| id_variant | string | tidak | harus milik produk ini; WAJIB kosong untuk `size_chart` |
| alt_text | string | tidak | maks 150 |
| is_primary | boolean | tidak | gambar pertama otomatis utama; size chart selalu `false` |
| urutan | integer | tidak | min 0 (default lanjut dari maks per jenis) |

Respons `201`.

Batas: maksimal 5 foto produk (`type = 'produk'`) per produk dan maksimal 1 size chart. Size chart tidak dihitung ke kuota foto.

### 6.3. Hapus Gambar
- **Method / Path**: `DELETE /api/images/{id}`
- **Akses**: admin
- **Deskripsi**: Menghapus file dan record gambar. Bila gambar utama terhapus, gambar berikutnya dipromosikan menjadi utama.

### 6.4. Daftar Gambar (Admin)
- **Method / Path**: `GET /api/admin/products/{id}/images`
- **Akses**: admin
- **Deskripsi**: Sama seperti 6.1, namun produk `draft`/`nonaktif` tetap terlihat. Query `?type=produk|size_chart` tersedia.

### 6.5. Unggah Gambar (Admin)
- **Method / Path**: `POST /api/admin/products/{id}/images`
- **Akses**: admin
- Body dan batas sama seperti 6.2; dipakai untuk produk yang belum `aktif`.

### 6.6. Hapus Gambar (Scoped Produk)
- **Method / Path**: `DELETE /api/admin/products/{id}/images/{imageId}`
- **Akses**: admin
- **Deskripsi**: Menghapus gambar yang benar-benar milik produk pada path tersebut. Gambar milik produk lain menghasilkan `404`.

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

### 9.8. Publikasikan Voucher
- **Method / Path**: `POST /api/vouchers/{id}/publish`
- **Akses**: admin
- **Deskripsi**: Menjadikan voucher aktif (`status_voucher = aktif`). Idempoten — voucher yang sudah aktif tetap `200`.
- `422` `"Voucher sudah kedaluwarsa dan tidak dapat dipublikasikan."` bila `tanggal_selesai < sekarang`.
- `404` bila voucher tidak ada.

Respons `200`: `{ "success": true, "message": "Voucher berhasil dipublikasikan.", "data": { "id_voucher": "...", "kode_voucher": "VCH4WO8GR", "nama_voucher": "Voucher Uji", "tipe_diskon": "persentase", "nilai_diskon": "10.00", "status_voucher": "aktif", "tanggal_mulai": "...", "tanggal_selesai": "...", ... } }`

### 9.9. Tarik Voucher dari Publikasi
- **Method / Path**: `POST /api/vouchers/{id}/unpublish`
- **Akses**: admin
- **Deskripsi**: Mengembalikan voucher ke `status_voucher = nonaktif`. Pesan: `"Voucher berhasil ditarik dari publikasi."`, format respons sama dengan 9.8.

### 9.10. Ringkasan Voucher
- **Method / Path**: `GET /api/vouchers/summary`
- **Akses**: admin
- **Deskripsi**: Rekap seluruh voucher. Harus didaftarkan sebelum `GET /api/vouchers/{id}` agar tidak tertangkap wildcard.

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Ringkasan voucher berhasil diambil.",
  "data": { "total_voucher": 5, "aktif": 4, "nonaktif": 1, "berjalan": 2, "terjadwal": 1, "kadaluarsa": 1, "total_penggunaan": 2, "total_diskon_diberikan": 35000 }
}
```

`berjalan` = aktif dan berlaku sekarang; `terjadwal` = belum mulai; `kadaluarsa` = sudah lewat `tanggal_selesai`.

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
- **Deskripsi**: Membuat pesanan dari keranjang `aktif` user. Subtotal diambil dari harga varian saat checkout, **biaya pengiriman dihitung ulang di backend lewat RajaOngkir** (nilai `biaya_pengiriman` dari klien diabaikan), stok varian langsung dikurangi, keranjang ditandai `checkout`, dan riwayat status awal `menunggu_pembayaran` dibuat.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_user_address | string | ya | harus alamat milik user yang login |
| kurir | string | ya | salah satu: `jne`, `pos`, `tiki`, `sicepat`, `jnt`, `anteraja`, `jxpress`, `lex`, `lion`, `ninja` |
| layanan | string | ya | kode/nama layanan dari `POST /api/shipping/cost` (mis. `CTC`, `JTR`, `REG`) |
| kode_voucher | string | tidak | maks 50, divalidasi seperti `POST /api/vouchers/validate` |
| biaya_pengiriman | numeric | tidak | **diabaikan** (dipertahankan hanya untuk kompatibilitas klien) |
| catatan | string | tidak | catatan pesanan |

Alur perhitungan ongkir: berat total = Σ(`berat_gram` varian × jumlah), tujuan = `id_tujuan_rajaongkir` alamat, lalu `POST /api/shipping/cost` ke RajaOngkir; biaya pesanan diambil dari layanan yang cocok dengan `layanan`.

- `404` bila `id_user_address` bukan milik user.
- `422` bila keranjang kosong, stok tidak mencukupi, varian/produk tidak aktif, voucher tidak valid, alamat belum punya `id_tujuan_rajaongkir` (`ALAMAT_TANPA_TUJUAN_RAJAONGKIR`), atau `layanan` tidak tersedia pada kurir (`LAYANAN_TIDAK_TERSEDIA`).
- `422` bila RajaOngkir menolak permintaan (`RAJAONGKIR_INVALID_REQUEST`, `RAJAONGKIR_DESTINATION_NOT_FOUND`, `RAJAONGKIR_NO_SERVICE`).
- `502` bila upstream RajaOngkir bermasalah (`RAJAONGKIR_UPSTREAM_ERROR`, `RAJAONGKIR_UNAUTHORIZED`, `RAJAONGKIR_NOT_CONFIGURED`, `RAJAONGKIR_INVALID_RESPONSE`).
- `503` bila koneksi ke RajaOngkir gagal (`RAJAONGKIR_UNAVAILABLE`).
- Kegagalan ongkir (422/502/503) terjadi sebelum transaksi database → pesanan **tidak** dibuat, stok dan keranjang tidak berubah.
- `500` bila terjadi kesalahan saat transaksi (perubahan di-rollback).
- Respons `201`: `data` berisi objek pesanan beserta `items`.
- Endpoint `GET /api/shipping/destinations` untuk memilih alamat tujuan dan `POST /api/shipping/cost` untuk menampilkan pilihan layanan/ongkir sebelum checkout.

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

### 11.4b. Unduh Invoice Pesanan
- **Method / Path**: `GET /api/orders/{id}/invoice`
- **Akses**: login (pemilik)
- **Deskripsi**: Mengembalikan berkas **PDF** invoice pesanan (dibangun dari data database: `orders`, `order_items`, `payments`, `voucher_usages`).
- Header respons: `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="invoice-{nomor_pesanan}.pdf"`.
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

### 11.7. Ringkasan Pesanan (Admin)
- **Method / Path**: `GET /api/admin/orders/summary`
- **Akses**: admin
- **Deskripsi**: Rekap pesanan. Didaftarkan **sebelum** `/admin/orders/{id}` agar tidak tertangkap wildcard.

Query: `status` (salah satu status pesanan), `dari` (tanggal), `sampai` (tanggal).

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Ringkasan pesanan berhasil diambil.",
  "data": { "total_pesanan": 3, "total_nilai": 400000, "rata_rata_nilai": 133333.33, "per_status": { "menunggu_pembayaran": 1, "dibayar": 1, "diproses": 0, "dikirim": 0, "selesai": 0, "dibatalkan": 1 } }
}
```

- `total_nilai` = Σ `total_pembayaran` pesanan; `rata_rata_nilai` dibulatkan 2 desimal (`0` bila tidak ada data).
- `422` bila `status` tidak dikenal atau tanggal tidak valid.

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

### 12.4. Ringkasan Pembayaran (Admin)
- **Method / Path**: `GET /api/admin/payments/summary`
- **Akses**: admin
- **Deskripsi**: Rekap pembayaran (jumlah, nilai, dan sebaran status/provider).

Query: `status` (`menunggu` / `dibayar` / `ditolak` / `kedaluwarsa` / `dibatalkan`), `provider`, `dari` (tanggal), `sampai` (tanggal).

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Ringkasan pembayaran berhasil diambil.",
  "data": { "total_pembayaran": 4, "total_nilai": 475000, "nilai_dibayar": 250000, "per_status": { "menunggu": 2, "dibayar": 1, "ditolak": 0, "kedaluwarsa": 0, "dibatalkan": 1 }, "per_provider": { "manual": 1, "midtrans": 3 } }
}
```

- `nilai_dibayar` = Σ pembayaran berstatus `dibayar`.
- `422` bila nilai filter tidak valid.

### 12.5. Webhook Notifikasi Midtrans
- **Method / Path**: `POST /api/payments/midtrans/notification`
- **Akses**: publik (dipanggil server Midtrans; diverifikasi lewat `signature_key`)
- **Deskripsi**: Menerima notifikasi status transaksi, diverifikasi dengan signature `sha512(order_id + status_code + gross_amount + server_key)`, lalu memperbarui `payments` dan `orders`. Notifikasi dengan signature **valid** dicatat pada tabel `webhook_logs`.

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

- Signature tidak valid → `403` (hanya dicatat ke application log, tidak menulis database).
- Melebihi batas per IP (`throttle:midtrans-webhook`, default 60/menit, `MIDTRANS_WEBHOOK_THROTTLE`) → `429`.
- Nominal `gross_amount` tidak cocok dengan `total_pembayaran` → respons `200` dengan `success: false`.
- Notifikasi yang sudah pernah diproses (status pembayaran terminal) diabaikan (idempoten).

---

## 13. Utility

### 13.1. Tes Koneksi
- **Method / Path**: `GET /api/test-json`
- **Akses**: publik
- **Deskripsi**: Endpoint uji cepat. Mengembalikan teks `TEST LARAVEL BERHASIL`.

---

## 14. Ongkos Kirim (RajaOngkir)

Integrasi API resmi RajaOngkir (Komerce) — `https://rajaongkir.komerce.id/api/v1`. Origin pengiriman toko diambil dari tabel `settings` (`rajaongkir.origin_id`, bisa diubah admin via 16.1) dengan fallback `RAJAONGKIR_ORIGIN_ID` di `.env` — bukan dari klien. Daftar `courier` yang diterima = kurir **aktif** menurut konfigurasi admin (16.4/16.5; default semua aktif sehingga perilaku tidak berubah).

### 14.1. Cari Tujuan Pengiriman
- **Method / Path**: `GET /api/shipping/destinations`
- **Akses**: login
- **Deskripsi**: Mencari tujuan pengiriman (per kelurahan). Hasil di-cache agar hemat kuota.

Query: `search` (wajib, 2-100 karakter — nama kelurahan/kecamatan/kota/kode pos).

Respons `200`:

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

`id` inilah yang disimpan ke `user_addresses.id_tujuan_rajaongkir`. Bila tidak ada hasil → `200` dengan `data: []`.

### 14.2. Cek Ongkos Kirim
- **Method / Path**: `POST /api/shipping/cost`
- **Akses**: login

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| destination | integer | ya | ID tujuan (per kelurahan) dari 14.1 |
| weight | integer | ya | berat total gram, 1-30000 |
| courier | string | tidak | satu kode kurir; kosong = seluruh kurir aktif. Salah satu dari: `jne`, `pos`, `tiki`, `sicepat`, `jnt`, `anteraja`, `ninja`, `lion`, `ide`, `sap`, `ncs`, `rex`, `rpx`, `sentral`, `star`, `wahana`, `spx` |
| couriers | array | tidak | alternatif `courier`, banyak kurir sekaligus (boleh juga dipakai untuk kurir aktif tertentu) |

Respons `200`:

```json
{
  "success": true,
  "message": "Biaya pengiriman berhasil dihitung.",
  "data": [
    {
      "name": "JNE",
      "code": "jne",
      "service": "CTC",
      "description": "Layanan Reguler",
      "cost": 9000,
      "etd": "2-3"
    }
  ]
}
```

Kode error (body `{ "success": false, "message": "<KODE>" }`):

| Status | Kode | Arti |
|---|---|---|
| 422 | `RAJAONGKIR_INVALID_REQUEST` | Parameter ditolak RajaOngkir |
| 422 | `RAJAONGKIR_DESTINATION_NOT_FOUND` | Tujuan tidak dikenal |
| 422 | `RAJAONGKIR_NO_SERVICE` | Tidak ada layanan untuk tujuan/kurir |
| 502 | `RAJAONGKIR_UPSTREAM_ERROR` | RajaOngkir bermasalah |
| 502 | `RAJAONGKIR_UNAUTHORIZED` | API key ditolak |
| 502 | `RAJAONGKIR_NOT_CONFIGURED` | API key/origin belum diisi |
| 502 | `RAJAONGKIR_INVALID_RESPONSE` | Format respons tidak dikenal |
| 503 | `RAJAONGKIR_UNAVAILABLE` | Koneksi ke RajaOngkir gagal |

Catatan ketahanan: request diulang otomatis 1 kali (delay 250 ms) hanya untuk gangguan koneksi / upstream `5xx`; error `4xx` tidak diulang. TLS memakai CA sistem, dan otomatis memakai `cacert.pem` bawaan SDK Midtrans bila CA sistem kosong (`RAJAONGKIR_VERIFY` untuk memaksa path file). Verifikasi cepat semua endpoint pihak ketiga: `php artisan external:check`.

### 14.3. Cek Ongkos Kirim Serentak (JNE, J&T, POS)
- **Method / Path**: `POST /api/rajaongkir/check-all`
- **Akses**: login (pembatas laju `throttle:rajaongkir-check` — 20/menit per pengguna, 60/menit per IP → `429` bila lewat)
- **Deskripsi**: Menghitung tarif **JNE (`jne`), J&T (`jnt`), dan POS (`pos`) sekaligus** untuk satu tujuan & berat, lalu **menyimpan hasilnya di cache selama 7 hari** untuk menghemat kuota RajaOngkir. Endpoint ini murni baca — **tidak pernah membuat baris `shipments`** dan tidak mengubah data apa pun.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| destination | integer | ya | ID tujuan (per kelurahan) dari 14.1, min 1 |
| weight | integer | ya | berat total gram, 1-30000 |

Respons `200`:

```json
{
  "success": true,
  "message": "Biaya pengiriman berhasil dihitung.",
  "data": [
    { "name": "POS", "code": "pos", "service": "Pos Reguler", "description": "Reguler", "cost": 17000, "etd": "3-5" },
    { "name": "J&T", "code": "jnt", "service": "EZ", "description": "Ekonomis", "cost": 18000, "etd": "3-4" },
    { "name": "JNE", "code": "jne", "service": "REG", "description": "Reguler", "cost": 20000, "etd": "2-3" }
  ],
  "data_per_kurir": [
    { "courier": "jne", "tersedia": true, "layanan": [
      { "name": "JNE", "code": "jne", "service": "REG", "description": "Reguler", "cost": 20000, "etd": "2-3" }
    ] },
    { "courier": "jnt", "tersedia": true, "layanan": [
      { "name": "J&T", "code": "jnt", "service": "EZ", "description": "Ekonomis", "cost": 18000, "etd": "3-4" }
    ] },
    { "courier": "pos", "tersedia": true, "layanan": [
      { "name": "POS", "code": "pos", "service": "Pos Reguler", "description": "Reguler", "cost": 17000, "etd": "3-5" }
    ] }
  ],
  "meta": {
    "kurir_dicek": ["jne", "jnt", "pos"],
    "kurir_tersedia": ["pos", "jnt", "jne"],
    "kurir_tanpa_tarif": [],
    "dari_cache": [],
    "diambil_dari_api": ["jne", "jnt", "pos"],
    "cache_ttl_hari": 7,
    "berat_gram": 1000
  }
}
```

Struktur `data_per_kurir` (dilengkapi agar frontend mudah menampilkan ketiga kurir):

- Selalu memuat **ketiga kurir** (`jne`, `jnt`, `pos`) sesuai urutan, walau ada yang tidak melayani rute.
- `tersedia` = `true` bila kurir punya tarif, `false` bila tidak (kurir tetap tampil dengan `layanan: []`).
- `layanan` = daftar layanan kurir tersebut (kosong bila `tersedia: false`).
- Bila kamu hanya ingin menampilkan kurir yang ada tarifnya, cukup tampilkan entri dengan `tersedia: true` (mis. hanya `jne` yang tampil bila `jnt`/`pos` tidak melayani rute itu).
- `data` (datar, terurut termurah) tetap dipertahankan untuk kompatibilitas; `data_per_kurir` adalah bentuk tambahan, bukan pengganti.

Perilaku cache & kuota:

- Tarif dihitung **per kurir** dan disimpan terpisah (kunci cache memuat origin, tujuan, berat, kode kurir, dan versi skema) dengan masa berlaku **7 hari** (`cache_ttl_hari`).
- Kurir yang tarifnya sudah ada di cache **tidak dipanggil ulang** ke API. Bila sebagian kurir sudah ter-cache, hanya sisanya yang diambil — dan selalu dalam **satu** panggilan upstream (parameter `courier` dipisah titik dua), bukan satu panggilan per kurir.
- `dari_cache` = kurir yang dilayani dari cache (tanpa request upstream); `diambil_dari_api` = kurir yang benar-benar diambil dari RajaOngkir pada panggilan ini.
- Hasil "kurir tidak menyediakan tarif" (mis. `404`/`400`) juga ikut di-cache karena tetap memakan kuota; kurir tersebut muncul di `kurir_tanpa_tarif`.
- Bila pengambilan gagal (upstream error/koneksi), hasil **tidak** di-cache sehingga panggilan berikutnya bisa mencoba lagi.
- `Cache::lock` mencegah beberapa permintaan bersamaan menembak API berkali-kali untuk kunci yang sama (anti cache-stampede).
- Hasil `data` digabung dari semua kurir dan diurutkan dari tarif **termurah**.

Kode error (sama seperti 14.2, body `{ "success": false, "message": "<KODE>" }`): `422` (`RAJAONGKIR_INVALID_REQUEST`), `429` (`RAJAONGKIR_QUOTA_EXCEEDED`), `502` (`RAJAONGKIR_UPSTREAM_ERROR`, `RAJAONGKIR_UNAUTHORIZED`, `RAJAONGKIR_NOT_CONFIGURED`, `RAJAONGKIR_INVALID_RESPONSE`), `503` (`RAJAONGKIR_UNAVAILABLE`). Bila tidak ada kurir yang menyediakan tarif, tetap `200` dengan `data: []` dan `kurir_tanpa_tarif` terisi; `data_per_kurir` tetap memuat ketiga kurir dengan `tersedia: false`.

---

## 15. Pengiriman & Pemenuhan (Admin)

Semua endpoint grup ini: `auth:sanctum` + `admin` (`401` tanpa token, `403` untuk pelanggan).

### 15.1. Daftar Pengiriman
- **Method / Path**: `GET /api/admin/shipments`
- **Akses**: admin
- **Deskripsi**: Data tabel `shipments` beserta relasi pesanan (`order.id_order`, `nomor_pesanan`, `status_pesanan`, `nama_penerima`). Paginasi default 15.

Query: `status` (`menunggu` / `diproses` / `dikirim` / `dalam_perjalanan` / `diterima` / `dibatalkan`), `kurir` (like), `search` (nomor resi / nomor pesanan), `dari` (tanggal), `sampai` (tanggal), `page`, `per_page` (1-100).

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Daftar pengiriman berhasil diambil.",
  "data": [
    {
      "id_shipment": "c92c2906-09cf-48bd-809a-8a35b7995725",
      "id_order": "7b23ee75-92f7-44b4-a5c6-e7ce20306e9f",
      "provider": "rajaongkir",
      "kurir": "JNE",
      "layanan_pengiriman": "Reguler",
      "kode_layanan": "REG",
      "berat_gram": 500,
      "biaya_pengiriman": "9000.00",
      "nomor_resi": null,
      "status_pengiriman": "menunggu",
      "tanggal_dikirim": null,
      "tanggal_diterima": null,
      "dibuat_pada": "2026-10-19T15:03:08.000000Z",
      "diperbarui_pada": "2026-10-19T15:03:08.000000Z",
      "order": { "id_order": "7b23ee75-92f7-44b4-a5c6-e7ce20306e9f", "nomor_pesanan": "ORD-SHIP-B", "status_pesanan": "menunggu_pembayaran", "nama_penerima": "Pelanggan 7IJD" }
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 2, "last_page": 1 }
}
```

> Tabel `shipments` dan `shipment_histories` berasal dari dump database (tanpa file migrasi baru, mengikuti konvensi repo).

### 15.2. Ubah Status Pengiriman
- **Method / Path**: `PUT /api/admin/shipments/{id}/status`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| status_pengiriman | string | ya | `menunggu` / `diproses` / `dikirim` / `dalam_perjalanan` / `diterima` / `dibatalkan` |
| nomor_resi | string | tidak | maks 100; **wajib** untuk `dikirim`/`dalam_perjalanan` |
| lokasi | string | tidak | maks 255, dicatat ke riwayat |
| keterangan | string | tidak | dicatat ke riwayat |

- Setiap perubahan dicatat ke `shipment_histories` (`status_sebelumnya`, `status_baru`, `lokasi`, `keterangan`, `waktu_perubahan`) dalam satu transaksi.
- `dikirim` → `tanggal_dikirim` diisi; `diterima` → `tanggal_diterima` diisi; `nomor_resi` lama dipertahankan bila tidak dikirim ulang.
- `422` bila status sama dengan saat ini, atau tanpa nomor resi untuk `dikirim`/`dalam_perjalanan` (`"Nomor resi wajib diisi sebelum paket dikirim."`), atau `status_pengiriman` di luar daftar.
- `404` bila pengiriman tidak ada.
- Respons `200`: objek pengiriman + `histories` + `order` (contoh aktual terlihat pada hasil pengujian — `status_sebelumnya: "menunggu"`, `status_baru: "dikirim"`).

### 15.3. Daftar Pemenuhan Pesanan
- **Method / Path**: `GET /api/admin/order-fulfillments`
- **Akses**: admin
- **Deskripsi**: Catatan pemenuhan (tabel `order_fulfillments`, migrasi baru) beserta pesanan dan pencatatnya. Urut `dibuat_pada` desc lalu `urutan` desc. Paginasi default 15.

Query: `status` (`persiapan` / `dikemas` / `siap_kirim` / `selesai`), `id_order`, `page`, `per_page` (1-100). `422` bila `status` di luar daftar.

### 15.4. Catat Pemenuhan Pesanan
- **Method / Path**: `POST /api/admin/orders/{id}/fulfillments`
- **Akses**: admin

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| status_fulfillment | string | ya | `persiapan` / `dikemas` / `siap_kirim` / `selesai` |
| catatan | string | tidak | keterangan tambahan |

Respons `201` (aktual):

```json
{
  "success": true,
  "message": "Pemenuhan pesanan berhasil dicatat.",
  "data": {
    "id_fulfillment": "897a2e90-60c5-4d3d-971e-9ce0034b2a5c",
    "id_order": "b2d0fb6b-5613-46e1-8aa0-675e97c49347",
    "status_fulfillment": "persiapan",
    "catatan": "Barang disiapkan dari gudang",
    "diubah_oleh": "947bf6bb-4a4d-4d28-8026-6e72a8f4e059",
    "urutan": 1,
    "diperbarui_pada": "2026-10-19T15:03:04.000000Z",
    "dibuat_pada": "2026-10-19T15:03:04.000000Z",
    "order": { "id_order": "b2d0fb6b-5613-46e1-8aa0-675e97c49347", "nomor_pesanan": "ORD-FUL-B", "status_pesanan": "dibayar" }
  }
}
```

Aturan bisnis:
- Pesanan `menunggu_pembayaran` / `dibatalkan` / `selesai` → `422` (mis. `"Pesanan belum dibayar dan belum dapat dipenuhi."`).
- Status sama dengan catatan terakhir → `422` `"Status pemenuhan sama dengan catatan terakhir."`
- Setiap catatan mendapat `urutan` menaik per pesanan (dihitung dalam transaksi + lock), sehingga "catatan terakhir" selalu dapat ditentukan meski dibuat pada detik yang sama.
- `404` bila pesanan tidak ada; `422` bila `status_fulfillment` di luar daftar.

---

## 16. Konfigurasi RajaOngkir (Admin)

Sumber konfigurasi: tabel `settings` (kunci `rajaongkir.origin_id` dan `rajaongkir.kurir_aktif` berformat JSON) menang, fallback `config/services` + `.env`. **API key tidak pernah dikirim balik** — hanya indikator `api_key_terisi`.

### 16.1. Baca Konfigurasi Pengiriman
- **Method / Path**: `GET /api/admin/shipping/config`
- **Akses**: admin

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Konfigurasi pengiriman berhasil diambil.",
  "data": { "origin_id": "82935", "sumber_origin": "env", "base_url": "https://rajaongkir.komerce.id/api/v1", "api_key_terisi": true, "kurir_aktif": ["jne", "pos", "tiki", "sicepat", "jnt", "anteraja", "jxpress", "lex", "lion", "ninja"] }
}
```

`sumber_origin` = `settings` bila admin pernah mengubah origin, selain itu `env`.

### 16.2. Ubah Origin Pengiriman
- **Method / Path**: `PUT /api/admin/shipping/config`
- **Akses**: admin
- Body: `{ "origin_id": "99999" }` — wajib, hanya angka, maks 20 karakter.
- Tersimpan ke `settings` (`sumber_origin` berubah jadi `settings`) dan **langsung dipakai** perhitungan ongkir (cache `settings:shipping.*` di-flush).
- `422` bila tidak valid (tidak ada data yang ditulis).

Respons `200` (aktual): `{ "success": true, "message": "Konfigurasi pengiriman berhasil diperbarui.", "data": { "origin_id": "99999", "sumber_origin": "settings", "base_url": "...", "api_key_terisi": true, "kurir_aktif": [...] } }`

### 16.3. Health Check Pengiriman
- **Method / Path**: `GET /api/admin/shipping/health`
- **Akses**: admin
- **Deskripsi**: Mengecek kelengkapan konfigurasi + koneksi HTTPS ke host RajaOngkir. **Tidak memanggil API RajaOngkir** (tanpa konsumsi kuota) — `memakai_kuota_api` selalu `false`.

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Kesehatan konfigurasi pengiriman berhasil diperiksa.",
  "data": { "status": "ok", "checks": { "api_key_terisi": true, "base_url_valid": true, "origin_terisi": true, "konektivitas_https": true }, "base_url": "https://rajaongkir.komerce.id/api/v1", "origin_id": "82935", "jumlah_kurir_aktif": 10, "memakai_kuota_api": false }
}
```

`status`: `belum_terkonfigurasi` (API key/origin kosong) → `degraded` (koneksi gagal) → `ok` (semua pemeriksaan lulus).

### 16.4. Daftar Kurir
- **Method / Path**: `GET /api/admin/shipping/couriers`
- **Akses**: admin

Respons `200` (aktual):

```json
{
  "success": true,
  "message": "Daftar kurir berhasil diambil.",
  "data": [ { "kode": "jne", "aktif": true }, { "kode": "pos", "aktif": true }, { "kode": "tiki", "aktif": true }, { "kode": "sicepat", "aktif": true }, { "kode": "jnt", "aktif": true }, { "kode": "anteraja", "aktif": true }, { "kode": "jxpress", "aktif": true }, { "kode": "lex", "aktif": true }, { "kode": "lion", "aktif": true }, { "kode": "ninja", "aktif": true } ],
  "meta": { "total": 10, "aktif": 10 }
}
```

### 16.5. Ubah Status Kurir
- **Method / Path**: `PUT /api/admin/shipping/couriers/{code}`
- **Akses**: admin
- Body: `{ "aktif": true }` (boolean wajib).
- `404` `"Kurir tidak ditemukan."` bila kode di luar 10 kurir yang didukung.
- `422` `"Minimal satu kurir harus tetap aktif."` bila menonaktifkan kurir terakhir.
- Kurir nonaktif langsung ditolak `POST /api/shipping/cost` dan checkout (`422` — pesan validasi `The selected courier is invalid.`).

Respons `200` (aktual): `{ "success": true, "message": "Status kurir berhasil diperbarui.", "data": { "kode": "pos", "aktif": false, "kurir_aktif": ["jne", "tiki", "sicepat", "jnt", "anteraja", "jxpress", "lex", "lion", "ninja"] } }`

### 16.6. Simulasi Ongkos Kirim
- **Method / Path**: `POST /api/admin/shipping/simulate`
- **Akses**: admin
- **Deskripsi**: Cek ongkir multi-kurir sekaligus. **Memakai kuota RajaOngkir** (1 panggilan per kurir).

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| destination | integer | ya | ID tujuan dari 14.1, min 1 |
| weight | integer | ya | gram, 1-30000 |
| couriers | array of string | tidak | harus kurir aktif; kosong = semua kurir aktif |

Respons `200` (aktual):

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

Aturan:
- Kegagalan per kurir tidak menggagalkan permintaan: entri menjadi `berhasil: false`, `error` berisi kode (mis. `RAJAONGKIR_UPSTREAM_ERROR`), `layanan: []`.
- `memakai_kuota_api` = jumlah panggilan API yang benar-benar dilakukan.
- Tanpa origin → `502` `RAJAONGKIR_NOT_CONFIGURED` tanpa panggilan API sama sekali.
- Kurir nonaktif yang diminta → `422` `"Tidak ada kurir aktif yang diminta."`.
- Input tidak valid (`destination`/`weight`/`couriers`) → `422`.

---

## 17. Inventaris (Admin)

Semua endpoint di bawah ini memerlukan akses **admin** (`peran = admin`). Setiap
perubahan stok tercatat di `inventory_movements`.

### 17.1. Daftar Inventaris
- **Method / Path**: `GET /api/admin/inventory`
- **Akses**: admin
- **Deskripsi**: Daftar varian beserta stok, harga modal, dan status stok.

Query: `search`, `category`, `status_stok` (`semua|aman|menipis|kritis`), `sort`
(`stok|stok_minimum|harga|sku|dibuat_pada`), `order` (`asc|desc`), `page`,
`per_page` (maks 100).

### 17.2. Detail Inventaris Varian
- **Method / Path**: `GET /api/admin/inventory/{variant_id}`
- **Akses**: admin
- **Deskripsi**: Detail satu varian + 50 pergerakan stok terakhir. `404` bila
  varian tidak ada. (Route statis lain didaftarkan lebih dulu agar tidak
  tertangkap wildcard ini.)

### 17.3. Riwayat Pergerakan Stok
- **Method / Path**: `GET /api/admin/inventory/movements`
- **Akses**: admin
- **Deskripsi**: Riwayat `inventory_movements`.

Query: `id_variant`, `jenis_pergerakan`
(`masuk|keluar|penyesuaian_masuk|penyesuaian_keluar`), `dari`, `sampai`,
`search`, `page`, `per_page` (maks 100).

### 17.4. Penyesuaian Stok
- **Method / Path**: `POST /api/admin/inventory/adjustments`
- **Akses**: admin
- **Deskripsi**: Menambah/mengurangi stok satu varian dan mencatat pergerakan.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_variant | string | ya | harus ada di `product_variants` |
| jenis_penyesuaian | string | ya | `masuk` atau `keluar` |
| jumlah | integer | ya | 1-1000000 |
| alasan | string | ya | maks 500 |
| referensi | string | tidak | maks 100 |

Aturan: pengurangan melebihi stok tersedia → `422` dan tidak ada perubahan
(transaksi dibatalkan).

### 17.5. Stok Menipis / Kritis
- **Method / Path**: `GET /api/admin/inventory/low-stock`
- **Akses**: admin
- **Deskripsi**: Varian berstatus kritis (stok 0) atau menipis
  (`stok_minimum > 0` dan `stok <= stok_minimum`).

Query: `level` (`menipis|kritis`), `search`, `page`, `per_page` (maks 100).

### 17.6. Ringkasan Inventaris
- **Method / Path**: `GET /api/admin/inventory/summary`
- **Akses**: admin
- **Deskripsi**: Agregat jumlah SKU/varian/produk, total unit stok, dan
  hitungan varian kritis/menipis/aman.

### 17.7. Valuasi Persediaan
- **Method / Path**: `GET /api/admin/inventory/valuation`
- **Akses**: admin
- **Deskripsi**: Nilai persediaan (`stok x harga_modal`) total dan per produk.
  Varian dengan `harga_modal` NULL tidak dihitung (bukan 0).

Query: `category`.

### 17.8. Ekspor Inventaris (CSV)
- **Method / Path**: `GET /api/admin/inventory/export`
- **Akses**: admin
- **Deskripsi**: Unduh CSV seluruh hasil filter (tanpa paginasi). Nilai yang
  berpotensi menjadi formula diberi awalan apostrof (anti CSV injection).

Query: `search`, `category`, `status_stok`.

---

## 18. Stock Opname (Admin)

Pemeriksaan stok fisik gudang. Membuat opname **tidak** mengubah stok; stok
baru diterapkan saat finalisasi (atau lewat penyesuaian inventaris per varian).
Semua akses **admin**.

Status opname: `draf` (belum diterapkan) dan `selesai` (selisih sudah
diterapkan).

> **Catatan**: tiga endpoint Stock Opname (18.1-18.3) sudah ada sejak awal dan
> sudah teruji. Dua endpoint terakhir (18.4 add item dan 18.5 finalisasi)
> **baru ditambahkan** pada pekerjaan ini dan tidak termasuk dalam hitungan
> 56 endpoint pada header dokumen.

### 18.1. Daftar Stock Opname
- **Method / Path**: `GET /api/admin/stock-opnames`
- **Akses**: admin
- **Deskripsi**: Daftar catatan opname + jumlah item.

Query: `status_opname` (`draf|selesai`), `dari`, `sampai`, `search`, `page`,
`per_page` (maks 100).

### 18.2. Buat Stock Opname
- **Method / Path**: `POST /api/admin/stock-opnames`
- **Akses**: admin
- **Deskripsi**: Membuat opname baru berisi hasil hitung. `stok_sistem` diambil
  dari stok terkini (row lock). Stok **tidak** diubah.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| catatan | string | tidak | maks 1000 |
| items | array | ya | 1-500 item |
| items[].id_variant | string | ya | harus ada di `product_variants` |
| items[].stok_fisik | integer | ya | 0-1000000000 |
| items[].catatan | string | tidak | maks 1000 |

Aturan: varian ganda dalam satu daftar → `422`.

Respons `201` memuat `status_opname: "draf"`, `total_item`, `total_selisih`
(jumlah aljabar selisih `stok_fisik - stok_sistem`), dan kode `OPN-YYYYMMDD-NNNN`.

### 18.3. Detail Stock Opname
- **Method / Path**: `GET /api/admin/stock-opnames/{id}`
- **Akses**: admin
- **Deskripsi**: Detail opname + seluruh item beserta varian produknya. `404`
  bila tidak ada.

### 18.4. Tambah Item Stock Opname
- **Method / Path**: `POST /api/admin/stock-opnames/{id}/items`
- **Akses**: admin
- **Deskripsi**: Menambah satu item hitung ke opname berstatus `draf`.
  `stok_sistem` diambil dari stok terkini; `total_item`/`total_selisih` header
  dihitung ulang.

Body:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id_variant | string | ya | harus ada di `product_variants` |
| stok_fisik | integer | ya | 0-1000000000 |
| catatan | string | tidak | maks 1000 |

Respons `201`. Aturan: opname tidak ada → `404`; status bukan `draf` → `422`;
varian sudah tercatat pada opname → `422`.

### 18.5. Finalisasi Stock Opname
- **Method / Path**: `POST /api/admin/stock-opnames/{id}/complete`
- **Akses**: admin
- **Deskripsi**: Menerapkan seluruh selisih ke `product_variants.stok` dan
  mencatat `inventory_movements` dalam **satu transaksi**. `stok_sistem` tiap
  item disegarkan dari stok terkini sebelum selisih dihitung ulang, sehingga
  perubahan stok setelah opname dibuat tetap terhitung. Mengubah status menjadi
  `selesai`.

Tanpa body. Respons `200`:

```json
{
  "success": true,
  "message": "Stock opname berhasil difinalisasi; selisih telah diterapkan ke stok.",
  "data": {
    "opname": { "status_opname": "selesai", "total_selisih": 1, "items": [] },
    "total_selisih": 1,
    "selisih_diterapkan": 2
  }
}
```

Aturan: opname tidak ada → `404`; opname sudah `selesai` → `422` (tidak dapat
difinalisasi ulang); opname tanpa item → `422`; varian tanpa selisih tidak
mencatat pergerakan (`selisih_diterapkan` hanya menghitung yang berselisih).

---

## 19. Konten CMS (Banner Hero, Kategori, Promo, Galeri, Profil, Fasilitas)

Konten yang dikelola admin tanpa mengubah kode frontend. Semua endpoint baca
bersifat publik; endpoint tulis wajib `admin`. Respons selalu memakai amplop
`{success, message, data}`. Kolom `urutan_tampilan` mengatur urutan kemunculan.

### 19.1. Agregat Beranda
- **Method / Path**: `GET /api/cms/homepage`
- **Akses**: publik
- **Deskripsi**: Menggabungkan seluruh konten beranda dalam satu panggilan:
  `hero`, `kategori`, `promo`, `galeri`, `profil_usaha`, `fasilitas`.

```json
{
  "success": true,
  "message": "Konten halaman utama berhasil diambil.",
  "data": {
    "hero": [ { "judul": "Koleksi Baru", "url_gambar": "http://.../storage/cms/hero/x.jpg" } ],
    "kategori": [ { "nama_kategori": "Batik Tulis", "url_gambar": "http://..." } ],
    "promo": { "nama_voucher": "Promo Spesial", "kode_voucher": "PROMO10", "url_gambar": "http://..." },
    "galeri": { "judul": "Galeri Pandansari", "gambar": [ { "url_gambar": "http://..." } ] },
    "profil_usaha": { "nama_usaha": "Batik Pandansari", "url_logo": "http://..." },
    "fasilitas": [ { "nama_fasilitas": "Parkir Luas" } ]
  }
}
```

Bagian tanpa data bernilai `null` (galeri/profil/promo) atau `[]` (hero,
kategori, fasilitas). `url_gambar`/`url_logo` dihasilkan dari disk `public`.

### 19.2. Promo Beranda
- **Method / Path**: `GET /api/cms/promo`
- **Akses**: publik
- **Deskripsi**: Mengambil **satu** voucher unggulan untuk Banner Promo. Memakai
  tabel `vouchers` (kolom `gambar`, `nama_voucher`, `deskripsi`), bukan tabel
  terpisah. Voucher dipilih dari yang `status_voucher = aktif` dan tanggal
  berjalan; diutamakan yang memiliki gambar, lalu yang paling baru dibuat.
  Tanpa voucher aktif → `data: null`.

### 19.3. Banner Hero (Publik)
- **Method / Path**: `GET /api/cms/hero`
- **Akses**: publik
- **Deskripsi**: Daftar slide `status_aktif = 1`, diurutkan `urutan_tampilan`.

### 19.4. Kategori CMS (Publik)
- **Method / Path**: `GET /api/cms/kategori`
- **Akses**: publik
- **Deskripsi**: Daftar kartu kategori beranda (`cms_kategori`) yang aktif,
  terurut. Berbeda dari kategori produk (`/api/categories`).

### 19.5. Galeri (Publik)
- **Method / Path**: `GET /api/cms/galeri`
- **Akses**: publik
- **Deskripsi**: Info galeri (`cms_galeri`, satu baris `id_galeri = 1`) beserta
  seluruh gambar (`cms_gambar_galeri`) terurut. Bila galeri tidak ada/nonaktif →
  `data: null`.

### 19.6. Profil Usaha (Publik)
- **Method / Path**: `GET /api/cms/profil-usaha`
- **Akses**: publik
- **Deskripsi**: Identitas toko (`profil_usaha`, satu baris `id_profil_usaha = 1`):
  nama, deskripsi, logo, email, telepon/WhatsApp, alamat, Google Maps, dan
  `jam_operasional` (array hari/jam). Bila belum diisi → `data: null`.

### 19.7. Fasilitas Usaha (Publik)
- **Method / Path**: `GET /api/cms/fasilitas`
- **Akses**: publik
- **Deskripsi**: Daftar fasilitas aktif, diurutkan `urutan_tampilan`.

### 19.8. Banner Hero — Kelola (Admin)
- **Method / Path**: `GET /api/admin/cms/hero`, `POST /api/admin/cms/hero`,
  `PUT /api/admin/cms/hero/{id}`, `DELETE /api/admin/cms/hero/{id}`
- **Akses**: admin
- **Deskripsi**: CRUD slide hero. POST/PUT memakai `multipart/form-data`
  (berkas `gambar`). Field wajib saat buat: `judul`, `teks_tombol`,
  `tautan_tombol`, `gambar`. `gambar` divalidasi `image|mimes:jpg,jpeg,png,webp|max:2048`.
  Mengganti gambar menghapus berkas lama; DELETE menghapus baris + berkas.

### 19.9. Kategori CMS — Kelola (Admin)
- **Method / Path**: `GET /api/admin/cms/kategori`, `POST /api/admin/cms/kategori`,
  `PUT /api/admin/cms/kategori/{id}`, `DELETE /api/admin/cms/kategori/{id}`
- **Akses**: admin
- **Deskripsi**: CRUD kartu kategori beranda. Saat buat: `nama_kategori`
  (unik), `judul`, `tautan_tujuan`, `gambar` wajib. `gambar` multipart.

### 19.10. Galeri — Kelola (Admin)
- **Method / Path**:
  - `GET /api/admin/cms/galeri` — info galeri + gambar
  - `PUT /api/admin/cms/galeri` — buat/ubah info galeri (`judul` wajib saat pertama)
  - `POST /api/admin/cms/galeri/gambar` — unggah gambar (`gambar` wajib, multipart)
  - `PUT /api/admin/cms/galeri/gambar/{id}` — ubah teks alternatif/urutan/ganti gambar
  - `DELETE /api/admin/cms/galeri/gambar/{id}` — hapus gambar + berkas
- **Akses**: admin
- **Deskripsi**: `cms_galeri` dirancang satu baris (`id_galeri = 1`). Unggah
  gambar sebelum galeri dibuat → `404`.

### 19.11. Profil Usaha — Kelola (Admin)
- **Method / Path**: `GET /api/admin/cms/profil-usaha`, `PUT /api/admin/cms/profil-usaha`
- **Akses**: admin
- **Deskripsi**: Baca/ubah profil. PUT bersifat parsial (`multipart/form-data`
  bila mengirim `logo`). Saat pertama kali dibuat `nama_usaha` wajib, jika tidak
  → `422`. `jam_operasional` berupa array objek, mis.
  `[{"hari":"Senin","jam_buka":"08:00","jam_tutup":"17:00"},{"hari":"Minggu","tutup":true}]`.

### 19.12. Fasilitas Usaha — Kelola (Admin)
- **Method / Path**: `GET /api/admin/cms/fasilitas`, `POST /api/admin/cms/fasilitas`,
  `PUT /api/admin/cms/fasilitas/{id}`, `DELETE /api/admin/cms/fasilitas/{id}`
- **Akses**: admin
- **Deskripsi**: CRUD fasilitas. Field: `nama_fasilitas` (wajib),
  `deskripsi`, `urutan_tampilan`, `status_aktif`.

### 19.13. Gambar Voucher
- **Method / Path**: `DELETE /api/vouchers/{id}/gambar`
- **Akses**: admin
- **Deskripsi**: Menghapus gambar voucher (mengosongkan kolom `gambar` dan
  menghapus berkas), tanpa menghapus vouchernya. Unggah/ganti gambar dilakukan
  lewat `POST /api/vouchers` dan `PUT /api/vouchers/{id}` (field multipart
  `gambar`). Respons voucher kini menyertakan `url_gambar`.

---

## 20. Panduan Ukuran (Size Guide)

Gambar panduan ukuran disimpan pada tabel `gambar_ukuran`. Kolomnya:
`id_gambar_ukuran` (BIGINT UNSIGNED, primary key, auto increment), `gambar`
(VARCHAR 255, path relatif berkas pada disk `public`), serta `dibuat_pada`
dan `diperbarui_pada`. Berkas diunggah lewat `multipart/form-data` dan hanya
gambar `jpg`/`jpeg`/`png`/`webp` maksimal 2 MB (2048 KB) yang diterima.

### 20.1. Daftar Gambar Panduan Ukuran (Publik)
- **Method / Path**: `GET /api/gambar-ukuran`
- **Akses**: publik
- **Deskripsi**: Menampilkan seluruh gambar panduan ukuran, diurutkan menurut
  `id_gambar_ukuran`. Respons menyertakan `url_gambar` (URL publik dari path
  tersimpan). Endpoint ini hanya untuk membaca; pengelolaan hanyalah admin.

Respons `200`:

```json
{
  "success": true,
  "message": "Daftar gambar panduan ukuran berhasil diambil.",
  "data": [
    {
      "id_gambar_ukuran": 1,
      "gambar": "panduan-ukuran/abc123.jpg",
      "url_gambar": "http://localhost/storage/panduan-ukuran/abc123.jpg",
      "dibuat_pada": "2026-10-21T10:00:00.000000Z",
      "diperbarui_pada": "2026-10-21T10:00:00.000000Z"
    }
  ]
}
```

Bila belum ada data → `200` dengan `data: []`.

### 20.2. Daftar Gambar Panduan Ukuran (Admin)
- **Method / Path**: `GET /api/admin/gambar-ukuran`
- **Akses**: admin
- **Deskripsi**: Sama seperti 20.1 (semua gambar), khusus untuk panel admin.

### 20.3. Unggah Gambar Panduan Ukuran (Admin)
- **Method / Path**: `POST /api/admin/gambar-ukuran`
- **Akses**: admin
- **Deskripsi**: Mengunggah satu gambar baru (`multipart/form-data`).

Request (`multipart/form-data`):

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| gambar | file | ya | gambar `jpg`/`jpeg`/`png`/`webp`, maksimal 2048 KB |

Respons `201`:

```json
{
  "success": true,
  "message": "Gambar panduan ukuran berhasil diunggah.",
  "data": {
    "id_gambar_ukuran": 2,
    "gambar": "panduan-ukuran/def456.png",
    "url_gambar": "http://localhost/storage/panduan-ukuran/def456.png",
    "dibuat_pada": "2026-10-21T10:05:00.000000Z",
    "diperbarui_pada": "2026-10-21T10:05:00.000000Z"
  }
}
```

### 20.4. Ganti Gambar Panduan Ukuran (Admin)
- **Method / Path**: `PUT /api/admin/gambar-ukuran/{id}`
- **Akses**: admin
- **Deskripsi**: Mengganti gambar lama dengan gambar baru (`multipart/form-data`,
  field `gambar`). Bila `gambar` tidak dikirim → data tidak berubah. Berkas
  lama dihapus **setelah** perubahan database berhasil dan hanya bila tidak
  dipakai baris lain; bila unggahan baru gagal (`422`), gambar lama tetap utuh.
- **Respons**: `200` (objek gambar terbaru), `404` bila `{id}` tidak ada,
  `422` bila berkas tidak valid.

### 20.5. Hapus Gambar Panduan Ukuran (Admin)
- **Method / Path**: `DELETE /api/admin/gambar-ukuran/{id}`
- **Akses**: admin
- **Deskripsi**: Menghapus baris + berkasnya. Berkas hanya dihapus bila tidak
  direferensikan baris lain (aman untuk berkas yang masih dipakai).
- **Respons**: `200` bila berhasil, `404` bila `{id}` tidak ada.

Kode status: `401` tanpa token, `403` bila bukan admin, `422` validasi berkas
gagal, `404` data tidak ditemukan.

---

## 21. Laporan Keuangan (Admin)

Semua angka pada modul ini dihitung oleh `PaymentMetricService` yang sama
dengan `GET /api/admin/dashboard/*`, sehingga hasil laporan selalu konsisten
dengan dashboard untuk filter yang sama. Sumber data: tabel `payments` dan
`orders`.

> Catatan: `total_nilai_pesanan` adalah akumulasi nilai pesanan, **bukan laba
> bersih** — data biaya/pengeluaran belum tersedia dan refund belum didukung
> skema saat ini.

### 21.1. Ringkasan Laporan
- **Method / Path**: `GET /api/admin/reports/summary`
- **Akses**: admin
- **Deskripsi**: Ringkasan pendapatan, sebaran status pembayaran, dan nilai
  pesanan untuk satu periode.

Query:

| Param | Tipe | Keterangan |
|---|---|---|
| dari | date | awal periode (inklusif) |
| sampai | date | akhir periode (inklusif), harus ≥ `dari` |

Respons `200` (`data`):

```json
{
  "success": true,
  "message": "Ringkasan laporan keuangan berhasil diambil.",
  "data": {
    "periode": { "dari": "2026-10-01", "sampai": "2026-10-21" },
    "pendapatan": { "total_pendapatan": 1250000, "jumlah_transaksi_bayar": 8 },
    "pembayaran": {
      "total_transaksi": 10,
      "per_status": { "menunggu_pembayaran": 1, "berhasil": 8, "gagal": 1 },
      "nilai_per_status": { "menunggu_pembayaran": 0, "berhasil": 1250000, "gagal": 0 }
    },
    "pesanan": { "jumlah_pesanan": 9, "total_nilai_pesanan": 1300000 },
    "keterangan": "total_nilai_pesanan adalah akumulasi nilai pesanan, bukan laba bersih, ..."
  }
}
```

### 21.2. Buku Kas Transaksi
- **Method / Path**: `GET /api/admin/reports/transactions`
- **Akses**: admin
- **Deskripsi**: Daftar transaksi pembayaran dengan paginasi dan filter.

Query:

| Param | Tipe | Keterangan |
|---|---|---|
| dari | date | awal periode |
| sampai | date | akhir periode |
| status | string | status pembayaran (lihat `Payment::STATUSES`) |
| provider | string | penyedia pembayaran, maks 50 |
| jenis_transaksi | string | `pembayaran` |
| search | string | cari pada `provider_transaction_id`, `metode_pembayaran`, atau `nomor_pesanan`, maks 150 |
| sort | string | `waktu` (default), `jumlah_pembayaran`, `dibuat_pada` |
| order | string | `asc` / `desc` (default `desc`) |
| page | integer | min 1 |
| per_page | integer | 1..100, default 15 |

Respons `200` berisi `data` (array transaksi) + `meta` (`current_page`,
`per_page`, `total`, `last_page`). Tiap transaksi: `id_payment`, `id_order`,
`nomor_pesanan`, `waktu`, `jenis_transaksi`, `status_pembayaran`,
`jumlah_pembayaran`, `provider`, `metode_pembayaran`, `provider_transaction_id`,
`dibuat_pada`.

### 21.3. Detail Transaksi
- **Method / Path**: `GET /api/admin/reports/transactions/{id}`
- **Akses**: admin
- **Deskripsi**: Detail satu transaksi pembayaran beserta ringkasan pesanan dan
  item pesanan. `{id}` = `id_payment`.

Respons `200` berisi `id_payment`, `id_order`, `nomor_pesanan`, `waktu`,
`jenis_transaksi`, `status_pembayaran`, `jumlah_pembayaran`, `provider`,
`metode_pembayaran`, `provider_transaction_id`, `payment_reference`,
`expires_at`, `paid_at`, `dibuat_pada`, dan relasi `order` (beserta `items`).

`404` bila transaksi tidak ada. `payment_url` tidak diekspos (rahasia pelanggan).

### 21.4. Ekspor Laporan PDF
- **Method / Path**: `GET /api/admin/reports/export/pdf`
- **Akses**: admin
- **Deskripsi**: Unduh laporan keuangan sebagai PDF berisi ringkasan periode +
  tabel transaksi.

Query: `dari`, `sampai`, `status`, `provider` (sama seperti 21.1/21.2).

Respons `200` (bukan JSON):

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="laporan-keuangan-YYYY-MM-DD.pdf"
```

Jumlah baris transaksi pada PDF dibatasi oleh `ReportPdfService::MAX_TRANSACTION_ROWS`.

---

## Ringkasan Cepat

Seluruh **149 endpoint** yang aktif pada `routes/api.php`. Kolom *Akses*:
`publik` (tanpa token), `login` (Bearer token Sanctum, data milik sendiri),
`admin` (Bearer token + `peran = admin`).

| No | Method | Endpoint | Akses | Kegunaan |
|---|---|---|---|---|
| 1 | POST | /api/register | publik | Daftar akun baru (peran pelanggan) + kirim OTP verifikasi email |
| 2 | POST | /api/login | publik | Login email + password, mengembalikan token Sanctum |
| 3 | POST | /api/auth/google | publik | Login/daftar otomatis lewat ID Token Google |
| 4 | POST | /api/otp/verify-registration | publik | Verifikasi kode OTP registrasi |
| 5 | POST | /api/otp/resend-registration | publik | Kirim ulang OTP registrasi |
| 6 | POST | /api/forgot-password | publik | Minta kode OTP pemulihan password |
| 7 | POST | /api/forgot-password/verify-otp | publik | Verifikasi OTP lupa password, menghasilkan reset_token |
| 8 | POST | /api/forgot-password/reset | publik | Atur kata sandi baru memakai reset_token |
| 9 | POST | /api/forgot-password/resend-otp | publik | Kirim ulang OTP lupa password |
| 10 | POST | /api/logout | login | Cabut token yang sedang dipakai |
| 11 | GET | /api/me | login | Profil pengguna yang login |
| 12 | PUT | /api/me | login | Ubah profil (nama, telepon, alamat) |
| 13 | PUT | /api/me/password | login | Ganti kata sandi (butuh kata sandi lama) |
| 14 | POST | /api/me/google | login | Tautkan akun Google ke akun yang login |
| 15 | GET | /api/addresses | login | Daftar alamat milik sendiri |
| 16 | POST | /api/addresses | login | Tambah alamat |
| 17 | GET | /api/addresses/{id} | login | Detail alamat (punya sendiri) |
| 18 | PUT | /api/addresses/{id} | login | Ubah alamat |
| 19 | DELETE | /api/addresses/{id} | login | Hapus alamat |
| 20 | GET | /api/wishlist | login | Daftar wishlist sendiri |
| 21 | POST | /api/wishlist | login | Tambah produk ke wishlist |
| 22 | DELETE | /api/wishlist/{id} | login | Hapus item wishlist |
| 23 | GET | /api/cart | login | Lihat isi keranjang + subtotal |
| 24 | POST | /api/cart/items | login | Tambah item keranjang |
| 25 | PUT | /api/cart/items/{id} | login | Ubah jumlah item keranjang |
| 26 | DELETE | /api/cart/items/{id} | login | Hapus item keranjang |
| 27 | DELETE | /api/cart | login | Kosongkan keranjang |
| 28 | GET | /api/vouchers/available | login | Daftar voucher yang bisa dipakai sekarang |
| 29 | POST | /api/vouchers/validate | login | Validasi kode voucher + hitung diskon |
| 30 | GET | /api/voucher-usages/me | login | Riwayat pemakaian voucher sendiri |
| 31 | GET | /api/shipping/destinations | login | Daftar kota/provinsi tujuan (RajaOngkir) |
| 32 | POST | /api/shipping/cost | login | Hitung ongkos kirim ke tujuan |
| 33 | POST | /api/rajaongkir/check-all | login | Cek ongkos JNE/J&T/POS sekaligus (cache 7 hari) |
| 34 | POST | /api/checkout | login | Buat pesanan dari isi keranjang |
| 35 | GET | /api/orders | login | Daftar pesanan sendiri |
| 36 | GET | /api/orders/{id} | login | Detail pesanan sendiri |
| 37 | GET | /api/orders/{id}/invoice | login | Unduh invoice pesanan (PDF) |
| 38 | POST | /api/orders/{id}/payment | login | Buat transaksi pembayaran Midtrans |
| 39 | GET | /api/orders/{id}/payment | login | Detail status pembayaran pesanan |
| 40 | GET | /api/categories | publik | Daftar kategori produk |
| 41 | GET | /api/categories/{id} | publik | Detail kategori |
| 42 | GET | /api/products | publik | Daftar produk aktif (search, category, paginasi) |
| 43 | GET | /api/products/{id} | publik | Detail produk aktif + varian + galeri + size chart |
| 44 | GET | /api/products/{id}/variants | publik | Varian aktif dari produk aktif |
| 45 | GET | /api/products/{id}/images | publik | Gambar produk (`?type=produk` / `?type=size_chart`) |
| 46 | GET | /api/cms/homepage | publik | Agregat konten beranda |
| 47 | GET | /api/cms/promo | publik | Banner promo beranda |
| 48 | GET | /api/cms/hero | publik | Banner hero |
| 49 | GET | /api/cms/kategori | publik | Kategori tampilan CMS |
| 50 | GET | /api/cms/galeri | publik | Galeri foto |
| 51 | GET | /api/cms/profil-usaha | publik | Profil usaha |
| 52 | GET | /api/cms/fasilitas | publik | Fasilitas usaha |
| 53 | GET | /api/gambar-ukuran | publik | Daftar gambar panduan ukuran |
| 54 | POST | /api/payments/midtrans/notification | webhook publik | Notifikasi status pembayaran dari Midtrans (signature) |
| 55 | POST | /api/midtrans/notification | webhook publik | Alias URL webhook Midtrans (handler identik) |
| 56 | GET | /api/test-json | publik | Utility tes koneksi Laravel |
| 57 | GET | /api/users | admin | Daftar pengguna |
| 58 | POST | /api/users | admin | Tambah pengguna |
| 59 | GET | /api/users/{id} | admin | Detail pengguna |
| 60 | GET | /api/admin/users/summary | admin | Ringkasan pengguna (jumlah per peran/status) |
| 61 | GET | /api/admin/users/export | admin | Ekspor pengguna (CSV) |
| 62 | GET | /api/admin/products | admin | Semua produk (draft/nonaktif) + filter status/sort |
| 63 | GET | /api/admin/products/export | admin | Ekspor katalog produk (CSV) |
| 64 | GET | /api/admin/products/{id} | admin | Detail produk + seluruh varian (termasuk nonaktif) |
| 65 | POST | /api/admin/products/{id}/variants/bulk | admin | Simpan banyak varian sekaligus (SKU unik, maks 500 item) |
| 66 | GET | /api/admin/products/{id}/images | admin | Daftar gambar produk (produk draft/nonaktif ikut) |
| 67 | POST | /api/admin/products/{id}/images | admin | Unggah gambar (maks 5 foto produk + 1 size chart) |
| 68 | DELETE | /api/admin/products/{id}/images/{imageId} | admin | Hapus gambar scoped ke produk tersebut (promote utama baru) |
| 69 | POST | /api/products | admin | Buat produk (wajib 1 gambar sebelum diaktifkan) |
| 70 | PUT | /api/products/{id} | admin | Update produk (partial; `null` ditulis null) |
| 71 | DELETE | /api/products/{id} | admin | Soft delete produk (status nonaktif) |
| 72 | POST | /api/products/{id}/variants | admin | Tambah varian produk |
| 73 | PUT | /api/variants/{id} | admin | Update varian (SKU unik) |
| 74 | DELETE | /api/variants/{id} | admin | Nonaktifkan varian (soft delete) |
| 75 | POST | /api/products/{id}/images | admin | Unggah gambar produk (jalur lama) |
| 76 | DELETE | /api/images/{id} | admin | Hapus gambar produk (jalur lama, tanpa scoping) |
| 77 | GET | /api/vouchers | admin | Daftar voucher |
| 78 | POST | /api/vouchers | admin | Buat voucher (+ unggah gambar) |
| 79 | GET | /api/vouchers/summary | admin | Ringkasan voucher |
| 80 | POST | /api/vouchers/{id}/publish | admin | Publikasikan voucher |
| 81 | POST | /api/vouchers/{id}/unpublish | admin | Tarik voucher dari publikasi |
| 82 | GET | /api/vouchers/{id} | admin | Detail voucher |
| 83 | PUT | /api/vouchers/{id} | admin | Update voucher (+ ganti gambar) |
| 84 | DELETE | /api/vouchers/{id} | admin | Hapus voucher |
| 85 | DELETE | /api/vouchers/{id}/gambar | admin | Hapus gambar voucher (voucher tetap ada) |
| 86 | GET | /api/voucher-usages | admin | Riwayat pemakaian voucher semua pengguna |
| 87 | POST | /api/admin/categories | admin | Buat kategori |
| 88 | PUT | /api/admin/categories/{id} | admin | Update kategori |
| 89 | DELETE | /api/admin/categories/{id} | admin | Nonaktifkan kategori |
| 90 | GET | /api/admin/cms/hero | admin | Kelola banner hero |
| 91 | POST | /api/admin/cms/hero | admin | Tambah banner hero |
| 92 | PUT | /api/admin/cms/hero/{id} | admin | Update banner hero |
| 93 | DELETE | /api/admin/cms/hero/{id} | admin | Hapus banner hero |
| 94 | GET | /api/admin/cms/kategori | admin | Kelola kategori CMS |
| 95 | POST | /api/admin/cms/kategori | admin | Tambah kategori CMS |
| 96 | PUT | /api/admin/cms/kategori/{id} | admin | Update kategori CMS |
| 97 | DELETE | /api/admin/cms/kategori/{id} | admin | Hapus kategori CMS |
| 98 | GET | /api/admin/cms/galeri | admin | Kelola metadata galeri |
| 99 | PUT | /api/admin/cms/galeri | admin | Update metadata galeri |
| 100 | POST | /api/admin/cms/galeri/gambar | admin | Tambah foto galeri |
| 101 | PUT | /api/admin/cms/galeri/gambar/{id} | admin | Update foto galeri |
| 102 | DELETE | /api/admin/cms/galeri/gambar/{id} | admin | Hapus foto galeri |
| 103 | GET | /api/admin/cms/profil-usaha | admin | Ambil profil usaha |
| 104 | PUT | /api/admin/cms/profil-usaha | admin | Update profil usaha |
| 105 | GET | /api/admin/cms/fasilitas | admin | Kelola fasilitas usaha |
| 106 | POST | /api/admin/cms/fasilitas | admin | Tambah fasilitas |
| 107 | PUT | /api/admin/cms/fasilitas/{id} | admin | Update fasilitas |
| 108 | DELETE | /api/admin/cms/fasilitas/{id} | admin | Hapus fasilitas |
| 109 | GET | /api/admin/gambar-ukuran | admin | Kelola gambar panduan ukuran |
| 110 | POST | /api/admin/gambar-ukuran | admin | Unggah gambar panduan ukuran |
| 111 | PUT | /api/admin/gambar-ukuran/{id} | admin | Ganti gambar panduan ukuran |
| 112 | DELETE | /api/admin/gambar-ukuran/{id} | admin | Hapus gambar panduan ukuran |
| 113 | GET | /api/admin/orders | admin | Daftar seluruh pesanan |
| 114 | GET | /api/admin/orders/summary | admin | Ringkasan pesanan (omzet/jumlah per status) |
| 115 | GET | /api/admin/orders/{id} | admin | Detail pesanan |
| 116 | PUT | /api/admin/orders/{id}/status | admin | Ubah status pesanan |
| 117 | POST | /api/admin/orders/{id}/fulfillments | admin | Catat pemenuhan pesanan (resi/kurir) |
| 118 | GET | /api/admin/order-fulfillments | admin | Daftar pemenuhan pesanan |
| 119 | GET | /api/admin/payments | admin | Daftar seluruh transaksi pembayaran |
| 120 | GET | /api/admin/payments/summary | admin | Ringkasan pembayaran |
| 121 | GET | /api/admin/shipments | admin | Daftar pengiriman |
| 122 | PUT | /api/admin/shipments/{id}/status | admin | Ubah status pengiriman |
| 123 | GET | /api/admin/shipping/config | admin | Ambil konfigurasi RajaOngkir (origin, kurir) |
| 124 | PUT | /api/admin/shipping/config | admin | Ubah konfigurasi ongkos kirim |
| 125 | GET | /api/admin/shipping/health | admin | Health check koneksi RajaOngkir |
| 126 | GET | /api/admin/shipping/couriers | admin | Daftar kurir aktif |
| 127 | PUT | /api/admin/shipping/couriers/{code} | admin | Aktifkan/nonaktifkan kurir |
| 128 | POST | /api/admin/shipping/simulate | admin | Simulasi hitung ongkos kirim |
| 129 | GET | /api/admin/inventory | admin | Daftar inventaris/stok varian |
| 130 | GET | /api/admin/inventory/movements | admin | Riwayat pergerakan stok |
| 131 | POST | /api/admin/inventory/adjustments | admin | Penyesuaian stok (masuk/keluar) |
| 132 | GET | /api/admin/inventory/low-stock | admin | Daftar stok menipis/kritis |
| 133 | GET | /api/admin/inventory/summary | admin | Ringkasan inventaris |
| 134 | GET | /api/admin/inventory/valuation | admin | Valuasi persediaan |
| 135 | GET | /api/admin/inventory/export | admin | Ekspor inventaris (CSV) |
| 136 | GET | /api/admin/inventory/{variant_id} | admin | Detail stok satu varian |
| 137 | GET | /api/admin/stock-opnames | admin | Daftar stock opname |
| 138 | POST | /api/admin/stock-opnames | admin | Buat sesi stock opname |
| 139 | GET | /api/admin/stock-opnames/{id} | admin | Detail stock opname + selisih |
| 140 | POST | /api/admin/stock-opnames/{id}/items | admin | Tambah item hasil hitung fisik |
| 141 | POST | /api/admin/stock-opnames/{id}/complete | admin | Finalisasi stock opname dan sesuaikan stok |
| 142 | GET | /api/admin/dashboard/summary | admin | Ringkasan dashboard admin |
| 143 | GET | /api/admin/dashboard/revenue | admin | Grafik pendapatan dashboard |
| 144 | GET | /api/admin/dashboard/orders | admin | Grafik pesanan dashboard |
| 145 | GET | /api/admin/dashboard/aov | admin | Rata-rata nilai pesanan (AOV) |
| 146 | GET | /api/admin/reports/summary | admin | Ringkasan laporan keuangan (periode) |
| 147 | GET | /api/admin/reports/transactions | admin | Buku kas transaksi + paginasi/filter |
| 148 | GET | /api/admin/reports/transactions/{id} | admin | Detail satu transaksi pembayaran |
| 149 | GET | /api/admin/reports/export/pdf | admin | Unduh laporan keuangan (PDF) |
