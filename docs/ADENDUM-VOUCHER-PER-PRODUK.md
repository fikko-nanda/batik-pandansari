# Adendum API — Voucher Promo Terikat 1 Produk

> **Status:** ~~Draf untuk direview tim Backend~~ — **Diimplementasikan BE (2026-10-22)**; FE admin telah berpindah ke field resmi
> **Dibuat:** Frontend team (2026-10-22)
> **Relasi:** `list-api.md` §9 (Voucher), §9.4 (Validasi), §11 (Checkout), §19.2 (Promo Beranda)
> **Prioritas:** Normal — fitur baru; tidak memecah kontrak lama (semua field baru nullable,
> voucher tanpa peruntukan berperilaku persis seperti sekarang)

## 1. Latar Belakang

Saat ini hanya ada satu jenis promo: voucher yang berlaku untuk seluruh isi keranjang
(`POST /api/vouchers/validate` menghitung diskon dari `subtotal` total tanpa memandang produk).
Kebutuhan bisnis baru:

1. **Promo semua produk** — perilaku sekarang (tetap).
2. **Promo khusus 1 produk** — voucher hanya berlaku pada SATU produk tertentu.

Sebagai jembatan, frontend sudah bisa membuat voucher tipe 2 dan menyimpan referensi
produknya di tabel `vouchers` lewat konvensi **tag `[PROD:<id_product>]` di awal kolom
`deskripsi`** (konteks lengkap di §6). Namun tag ini **tidak dipakai backend**, sehingga
voucher tipe 2 saat ini masih bisa:

- dipakai di keranjang yang sama sekali tidak berisi produk terikat (asalkan subtotal
  memenuhi `minimal_pembelian`),
- dan memotong **seluruh subtotal** cart, bukan hanya produk terikat.

Tag ini perlu digantikan **kolom resmi + aturan validasi server** sebagaimana diusulkan
di bawah.

## 2. Keputusan Bisnis (disepakati owner toko)

> **Diskon hanya berlaku pada produk terikat.** Contoh: keranjang berisi produk A dan
> produk B, customer memakai kode voucher yang terikat produk A:

- Diskon dihitung **hanya dari subtotal item produk A** di keranjang.
- Item produk B tetap harga normal, tidak ikut terpotong.
- Bila keranjang **tidak memuat produk A sama sekali** → validasi/checkout ditolak `422`
  dengan pesan yang jelas (lihat §4.2).

## 3. Usulan Skema Database

Tabel `vouchers` ditambah **satu kolom**:

```sql
ALTER TABLE vouchers
  ADD COLUMN id_product_peruntukan CHAR(36) NULL AFTER kuota_per_pengguna,
  ADD CONSTRAINT fk_voucher_peruntukan
    FOREIGN KEY (id_product_peruntukan) REFERENCES products (id_product);
```

| Kolom | Tipe | Aturan |
|---|---|---|
| `id_product_peruntukan` | CHAR(36), nullable | `null` = promo semua produk (perilaku lama). Terisi = voucher hanya berlaku pada produk tersebut. Wajib menunjuk produk `aktif` saat voucher dipublikasikan. |

Field lama tidak berubah sama sekali; voucher lama (kolom `null`) tetap berlaku untuk
semua produk — **tidak ada data yang dimigrasi.**

## 4. Usulan Perubahan Endpoint

### 4.1. `POST /api/vouchers` (§9.2) dan `PUT /api/vouchers/{id}` (§9.6)

Body ditambah field **opsional**:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id_product_peruntukan` | string (UUID) | tidak | Default `null`. Bila terisi harus menunjuk produk yang ada. |

- Validasi bila terisi: produk tidak ditemukan → `422` `"id_product_peruntukan tidak ditemukan."`
- Hubungan dengan status produk: lihat §5 (edge case produk nonaktif/dihapus).

### 4.2. `POST /api/vouchers/validate` (§9.4) — inti implementasi

Definisi untuk voucher dengan `id_product_peruntukan` terisi:

```
subtotal_peruntukan = Σ (qty × harga varian, harga berlaku saat checkout)
                      untuk semua item keranjang yang
                      id_product item = id_product_peruntukan
                      (dengan status produk & varian `aktif`)
```

Cek bertahap — urutan setelah cek lama (kode ditemukan, voucher aktif, rentang tanggal):

1. Bila `subtotal_peruntukan == 0` (produk terikat tidak ada di keranjang):
   `422` → `"Voucher hanya berlaku untuk produk <nama_produk>."`
2. Bila `subtotal_peruntukan < minimal_pembelian`:
   `422` → `"Minimal belanja untuk produk <nama_produk> adalah Rp <format>."`
3. Bila lolos keduanya, hitung diskon **dari `subtotal_peruntukan`**, bukan `subtotal`:
   - `persentase`: `subtotal_peruntukan × nilai_diskon / 100`, dibatasi `maksimal_diskon`
     (bila diisi) dan tidak melebihi `subtotal_peruntukan`.
   - `nominal`: `min(nilai_diskon, subtotal_peruntukan)`.

Respons `data` **ditambah** field (agar frontend dapat menampilkan konteks):

```json
{
  "id_voucher": "...",
  "kode_voucher": "ALIPHEBAT",
  "nama_voucher": "Hebat Batik Alip",
  "tipe_diskon": "persentase",
  "nilai_diskon": 10.0,
  "minimal_pembelian": 30000.0,
  "maksimal_diskon": null,
  "subtotal": 260000.0,
  "id_product_peruntukan": "7c8c03b3-9cae-45f4-ab3c-d70b53851fbf",
  "nama_produk_peruntukan": "batik alip",
  "subtotal_peruntukan": 30000.0,
  "jumlah_diskon": 3000.0
}
```

> Catatan: `jumlah_diskon` adalah potongan yang diterapkan pada nilai pesanan — untuk voucher
> peruntukan ini dihitung dari subtotal item produk terikat saja. Voucher lama
> (`id_product_peruntukan = null`) → field baru bernilai `null` dan `jumlah_diskon` tetap
> dihitung dari `subtotal` seluruh cart (perilaku lama, tidak berubah).

### 4.3. Checkout — `POST /api/checkout` (§11.1)

- Bila body checkout memakai `id_voucher` milik voucher peruntukan, aturan **identik §4.2**
  diterapkan ulang server-side (diskon hanya dari subtotal produk terikat; cart tanpa produk
  terikat → `422` sebelum pesanan dibuat).
- Nilai diskon yang tersimpan pada pesanan (`total_diskon_voucher` / kolom terkait) harus
  `jumlah_diskon` hasil perhitungan §4.2 — bukan potongan atas subtotal penuh.

### 4.4. Respons Voucher — `GET /api/vouchers` (§9.1), Detail §9.5, Publish/Unpublish §9.8–9.9

- Respons voucher memuat field baru `id_product_peruntukan` + `nama_produk_peruntukan`
  (join `products.nama_produk`) — dipakai FE untuk badge "1 PRODUK: <nama>" di halaman admin.
- §9.10 `summary` — tidak perlu berubah (opsional saja).

### 4.5. `GET /api/cms/promo` (§19.2) — Banner beranda customer

Bila voucher unggulan memiliki peruntukan, `data` **ditambah** data produk agar FE tidak
perlu memanggil `GET /products/{id}` kedua kalinya:

```json
{
  "data": {
    "id_voucher": "...",
    "kode_voucher": "ALIPHEBAT",
    "nama_voucher": "…",
    "deskripsi": "…",
    "tipe_diskon": "persentase",
    "nilai_diskon": 10.0,
    "tanggal_mulai": "...",
    "tanggal_selesai": "...",
    "url_gambar": "https://…/storage/vouchers/x.jpg",
    "id_product_peruntukan": "…",
    "nama_produk_peruntukan": "batik alip",
    "harga_mulai_peruntukan": 30000.0,
    "url_gambar_peruntukan": "https://…/storage/products/…jpg"
  }
}
```

Alternatif bila tim BE tidak ingin mengubah respons: FE memanggil `GET /api/products/{id}`
sendiri menggunakan `id_product_peruntukan`. **Mohon konfirmasi salah satu opsi.**

## 5. Edge Cases yang Perlu Disepakati

| Kasus | Usulan |
|---|---|
| Produk terikat di-nonaktifkan setelah voucher aktif | validate/checkout → `422` `"Voucher hanya berlaku untuk produk <nama>."`; publish voucher lain dengan produk nonaktif → `422` |
| Produk dihapus (soft delete) | Pilihan A: set `id_product_peruntukan = null` (voucher otomatis menjadi "semua produk"). Pilihan B: voucher tetap terikat dan berguna `422` saat dipakai. Silakan pilih satu — FE mengikuti. |
| Variant produk terikat nonaktif, tapi produk aktif | Hanya variant `aktif` produk terikat yang dihitung masuk `subtotal_peruntukan` |
| `minimal_pembelian` voucher peruntukan | Disetel admin terhadap harga varian termurah (FE sudah mengisi otomatis saat memilih produk, tetap bisa diubah) — tidak wajib di-enforce ulang di server selain cek §4.2 langkah 2 |

## 6. Integrasi Frontend Saat Ini (konteks untuk Backend)

- **Form Buat Promo admin** (`admin/create-promo.blade.php`): pilihan "Semua Produk" /
  "Khusus 1 Produk". Tipe "Khusus 1 Produk" memakai dropdown produk (sumber:
  `GET /api/admin/products?per_page=100`) dan menyimpan id produk sebagai
  **tag `[PROD:<id>]` di awal kolom `deskripsi`** voucher — karena backend belum punya
  kolom resmi.
- Tag **hanya konvensi frontend**: admin menampilkan badge dari tag; backend
  mengabaikannya (inilah penyebab voucher tipe 2 masih bisa dipakai di produk lain).
- **Ketika kolom `id_product_peruntukan` siap, FE berpindah** (perubahan kecil): kirim field
  resmi di `POST /vouchers` / `PUT /vouchers/{id}`, **berhenti menulis tag di `deskripsi`**,
  dan parser tag lama tetap berjalan hanya sebagai tampilan legacy untuk voucher lama
  (voucher dengan tag di `deskripsi` dan tanpa `id_product_peruntukan` dianggap
  "semua produk" + badge legacy).

> **Status (2026-10-22): EXPIRED — misi selesai.** Tim BE telah mengimplementasikan fitur
> end-to-end (migrasi kolom FK, `VoucherService`/`VoucherController`/`CheckoutController`,
> respons CMS promo diperkaya, test suite 439 passed). FE (admin) sudah berpindah ke field
> resmi `id_product_peruntukan` dan tidak lagi menulis tag di `deskripsi`; fungsi
> `punyaTag/stripTag` di `admin/create-promo.blade.php` dipertahankan hanya untuk tampilan
> voucher legacy (mis. kode uji ALIPHEBAT yang bertag). Dokumen ini dibiarkan sebagai
> rekaman keputusan & kontrak.

## 7. Checklist untuk Tim Backend

- [x] Migrasi kolom `vouchers.id_product_peruntukan` (nullable, FK ke `products`)
- [x] Terima field opsional di `POST /vouchers` & `PUT /vouchers/{id}`
- [x] Implementasi aturan diskon per produk di `POST /vouchers/validate` (cek 3 langkah §4.2 + field baru pada respons)
- [x] Terapkan aturan setara pada `POST /checkout` (§4.3)
- [x] Sertakan `id_product_peruntukan` + `nama_produk_peruntukan` di respons daftar/detail/publish voucher (§4.4)
- [x] Putuskan edge case produk nonaktif/dihapus (§5 — nonaktif/dihapus → `422` saat validate/checkout/publish)
- [x] Konfirmasi bentuk `GET /cms/promo` (§4.5 — respons diperkaya data produk)
