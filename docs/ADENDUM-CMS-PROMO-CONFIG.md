# Adendum API — Konfigurasi Banner Promo Beranda (`cms_promo`)

> **Catatan:** Dokumen ini menjelaskan versi satu promo. Implementasi saat ini memakai multi-promo;
> lihat [`ADENDUM-CMS-PROMO-MULTI.md`](ADENDUM-CMS-PROMO-MULTI.md).
>
> **Status:** Draf untuk tim Backend — FE admin & landing sudah siap (2026-10-23)
> **Dibuat:** Frontend team (2026-10-23)
> **Relasi:** `list-api.md` §9 (Voucher), §9.2/§9.6 (CRUD voucher), §19.2 (`GET /cms/promo`)
> **Prioritas:** Normal — fitur baru; endpoint lama tidak dipecah (semua field baru nullable)

## 1. Latar Belakang

Banner promo di landing (`resources/views/components/promo-banner.blade.php`) saat ini **statis**
(gambar `katalog1.webp`, judul "Promo Spesial Koleksi Sarimbit", CTA `/katalog?category=sarimbit`).
Admin sudah bisa membuat voucher (semua produk / khusus 1 produk) lewat menu **Buat Promo**, tetapi:

1. Banner beranda **tidak otomatis** menampilkan voucher yang sedang aktif — `GET /api/cms/promo`
   (§19.2) memilih voucher secara otomatis sehingga admin tidak bisa menentukan voucher unggulan.
2. Admin **tidak bisa mengganti gambar** banner promo tanpa deploy (gambar masih aset statis).
3. Admin tidak bisa mengatur **badge** (mis. "EDISI TERBATAS") maupun **link tujuan CTA** banner.

FE membutuhkan **satu konfigurasi banner promo** yang bisa diatur dari tab baru
**Pengaturan → Kelola Promo**, sementara voucher tetap dikelola di halaman Buat Promo
(tidak ada duplikasi CRUD voucher).

`list-api.md` **belum memiliki** endpoint admin untuk konfigurasi ini, sehingga adendum ini
mengusulkan penambahan endpoint admin + penyesuaian respons endpoint publik.

## 2. Keputusan Bisnis (disepakati owner toko)

1. **Satu banner promo** di beranda, menunjuk **satu voucher unggulan** (pin).
2. **Gambar banner diunggah sendiri** oleh admin (kolom `gambar` pada `cms_promo`),
   **bukan** meminjam gambar voucher. Bila kosong → fallback ke gambar voucher (bila ada),
   lalu fallback tampilan statis FE.
3. **Nama & deskripsi promo** diambil *read-only* dari voucher terpilih (`nama_voucher`,
   `deskripsi`). Mengubahnya dilakukan di halaman Buat Promo, bukan di tab Kelola Promo.
4. **Badge** diisi bebas oleh admin (contoh "EDISI LEBARAN"). Kosong → FE menampilkan
   label bawaan "EDISI TERBATAS".
5. **Link tujuan CTA:**
   - Voucher **khusus 1 produk** → bila `tautan_tujuan` kosong, otomatis
     `/detail/{id_product_peruntukan}` (FE juga menghitung ini sebagai fallback).
   - Voucher **semua produk** → admin mengisi manual (contoh `/katalog`); kosong → `/katalog`.
6. Bila pin voucher kosong / voucher tidak lagi aktif / di luar periode → endpoint publik
   **fallback ke auto-pick** (§19.2 perilaku lama), bukan mengembalikan banner rusak.

## 3. Usulan Skema Database

Tabel baru **`cms_promo`** (satu baris konfigurasi, pola sama dengan `cms_hero` / `cms_profil_usaha`):

```sql
CREATE TABLE cms_promo (
  id_cms_promo   CHAR(36) PRIMARY KEY,
  id_voucher     CHAR(36) NULL,
  badge          VARCHAR(40) NULL,
  tautan_tujuan  VARCHAR(255) NULL,
  gambar         VARCHAR(255) NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT fk_cms_promo_voucher
    FOREIGN KEY (id_voucher) REFERENCES vouchers (id_voucher) ON DELETE SET NULL
);
```

| Kolom | Tipe | Aturan |
|---|---|---|
| `id_voucher` | CHAR(36), nullable | Voucher unggulan (pin). `null` = pakai auto-pick §19.2. Tidak wajib menunjuk voucher `aktif` saat disimpan (validasi dilakukan saat baca publik). |
| `badge` | VARCHAR(40), nullable | Label kecil di atas judul banner. |
| `tautan_tujuan` | VARCHAR(255), nullable | Relatif (`/katalog`, `/detail/...`) atau absolut `https://`. |
| `gambar` | VARCHAR(255), nullable | Path storage `cms/promo/<file>`; nullable → fallback gambar voucher. |

**Tidak ada perubahan pada tabel `vouchers`** — relasi hanya dibaca (`id_product_peruntukan`
sudah ada dari adendum voucher per-produk).

## 4. Usulan Perubahan Endpoint

### 4.1. `GET /api/admin/cms/promo` (baru)

Ambil konfigurasi banner promo. Bila belum ada baris → kembalikan `data: null` (bukan 404),
supaya FE bisa membedakan "belum diatur" dari "endpoint tidak ada".

**Respons 200:**

```json
{
  "success": true,
  "message": "Konfigurasi promo beranda.",
  "data": {
    "id_cms_promo": "uuid",
    "id_voucher": "uuid-voucher",
    "badge": "EDISI LEBARAN",
    "tautan_tujuan": null,
    "gambar": "cms/promo/abc123.webp",
    "url_gambar": "https://.../storage/cms/promo/abc123.webp",
    "updated_at": "2026-10-23T10:00:00.000000Z",
    "voucher": {
      "id_voucher": "uuid-voucher",
      "kode_voucher": "LEBARAN25",
      "nama_voucher": "Diskon Lebaran",
      "deskripsi": "Potongan 25% untuk koleksi terbaru.",
      "tipe_diskon": "persentase",
      "nilai_diskon": 25,
      "minimal_pembelian": "150000.00",
      "kuota_total": 100,
      "kuota_per_pengguna": 1,
      "tanggal_mulai": "2026-10-01",
      "tanggal_selesai": "2026-10-31",
      "status_voucher": "aktif",
      "id_product_peruntukan": null,
      "nama_produk_peruntukan": null
    }
  }
}
```

`voucher` boleh `null` bila `id_voucher` kosong. FE **defensif**: membaca `id_voucher`
top-level lebih dulu, lalu `voucher.id_voucher`.

### 4.2. `PUT /api/admin/cms/promo` (baru, multipart)

Upsert konfigurasi (satu baris). Karena PHP tidak mem-parsing body `PUT` multipart,
**FE mengirim `POST /api/admin/cms/promo` + `_method=PUT`** (pola sama dengan
`PUT /admin/cms/hero` dan `PUT /admin/cms/galeri`). Backend wajib mendukung keduanya.

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `id_voucher` | string (uuid) | ya | Harus ada di tabel `vouchers`. |
| `badge` | string | tidak | Maks 40 karakter. |
| `tautan_tujuan` | string | tidak | Maks 255; relatif diawali `/` atau URL absolut `http(s)://`. |
| `gambar` | file | tidak | `jpg,jpeg,png,webp`, maks **2 MB**. Bila dikirim → ganti file lama + hapus file lama. Bila tidak dikirim → gambar lama dipertahankan. |

**Respons 200** = bentuk yang sama dengan §4.1 (data konfigurasi terbaru).
**Respons 422** = `id_voucher` tidak ditemukan / format badge / link / gambar tidak valid.

### 4.3. `DELETE /api/admin/cms/promo/gambar` (baru, opsional)

Menghapus file gambar banner dan mengosongkan kolom `gambar` (konfigurasi & pin voucher tetap).
FE memakai ini untuk tombol **Hapus** pada tab Kelola Promo.

**Respons 200:** `{ "success": true, "message": "Gambar banner promo dihapus.", "data": null }`

### 4.4. `GET /api/cms/promo` (publik, §19.2 — disesuaikan)

Perubahan perilaku (respons lama dipertahankan aditif):

1. Bila baris `cms_promo` ada dan `id_voucher` menunjuk voucher **aktif & dalam periode**
   → pakai voucher tersebut (pin).
2. Selain itu → **auto-pick** seperti perilaku lama (voucher aktif dengan prioritas yang
   sudah ada), agar banner tidak pernah kosong saat ada voucher aktif.
3. Bila tetap tidak ada voucher → `data: null` (FE memakai banner statis).

**Field tambahan pada `data` (aditif):**

| Field | Sumber | Catatan |
|---|---|---|
| `badge` | `cms_promo.badge` | `null` → FE "EDISI TERBATAS" |
| `tautan_tujuan` | `cms_promo.tautan_tujuan` | `null` + voucher per-produk → FE `/detail/{id_product_peruntukan}` |
| `url_gambar` | **prioritas** `cms_promo.gambar`, fallback gambar voucher | URL publik storage |
| `id_product_peruntukan` | voucher | Untuk derivasi link otomatis di FE |
| `nama_produk_peruntukan` | voucher | Label cakupan promo |

Field voucher yang sudah ada (`nama_voucher`, `deskripsi`, `kode_voucher`, dst.) tetap
dikembalikan. Contoh:

```json
{
  "success": true,
  "message": "Promo beranda.",
  "data": {
    "id_voucher": "uuid",
    "kode_voucher": "LEBARAN25",
    "nama_voucher": "Diskon Lebaran",
    "deskripsi": "Potongan 25% untuk koleksi terbaru.",
    "badge": "EDISI LEBARAN",
    "tautan_tujuan": null,
    "url_gambar": "https://.../storage/cms/promo/abc123.webp",
    "id_product_peruntukan": null,
    "nama_produk_peruntukan": null
  }
}
```

## 5. Aturan Validasi & Edge Case

| Kasus | Perilaku |
|---|---|
| `id_voucher` tidak ada / sudah dihapus | `PUT` → `422`; `GET` publik → fallback auto-pick |
| Voucher `nonaktif` atau di luar periode | `PUT` tetap boleh (simpan), `GET` publik → fallback auto-pick |
| `id_voucher` null/baris belum ada | `GET` publik → auto-pick; `GET` admin → `data: null` |
| Voucher per-produk + `tautan_tujuan` kosong | Publik mengembalikan `tautan_tujuan: null`; FE CTA ke `/detail/{id_product_peruntukan}` |
| Gambar > 2 MB / mime tidak didukung | `422` dengan pesan jelas |
| Upload gambar baru saat gambar lama ada | File lama dihapus, kolom diganti |
| `DELETE /admin/cms/promo/gambar` saat gambar kosong | Tetap `200` (idempoten) |
| Baris `cms_promo` lebih dari satu | Backend wajib menjaga hanya satu baris (upsert) |

## 6. Konteks Frontend (sudah siap)

- Helper `resources/js/app.js`:
  - `PandansariAPI.cmsPromoPublik()` → `GET /cms/promo`
  - `PandansariAPI.admin.cmsPromoKonfig()` → `GET /admin/cms/promo`
  - `PandansariAPI.admin.cmsPromoKonfigSimpan(formData)` → `POST /admin/cms/promo` + `_method=PUT`
  - `PandansariAPI.admin.cmsPromoKonfigHapusGambar()` → `DELETE /admin/cms/promo/gambar`
  - `imgUrl()` menerjemahkan `cms/promo/<file>` → proxy `/media/cms-promo/<file>`
- Route proxy FE: `GET /media/cms-promo/{filename}` (`MediaProxyController::cmsPromo`).
- Tab **Pengaturan → Kelola Promo**:
  - dropdown voucher aktif & dalam periode (dari `GET /vouchers`),
  - pratinjau voucher terpilih (nama, deskripsi, kode, diskon, min. belanja, kuota, cakupan),
  - upload gambar banner (preview, ganti, hapus, maks 2 MB),
  - input badge (maks 40) dan link tujuan CTA (+ tombol "Isi otomatis" untuk voucher per-produk),
  - tombol **Simpan Banner Promo**.
  - Bila `GET /admin/cms/promo` mengembalikan `404/405`, tab menampilkan catatan
    "menunggu adendum BE" tanpa mengganggu tab lain.
- Landing `promo-banner.blade.php` membaca `GET /cms/promo`; `data: null`
  → tampilan statis yang sekarang.

## 7. Checklist Backend

- [ ] Migrasi tabel `cms_promo` (FK `id_voucher` `ON DELETE SET NULL`).
- [ ] `GET /api/admin/cms/promo` → `data: null` bila belum diatur.
- [ ] `PUT /api/admin/cms/promo` (dukung `_method=PUT` + multipart), upsert satu baris.
- [ ] Validasi `id_voucher` exists, badge ≤ 40, link relatif/absolut, gambar ≤ 2 MB + mime.
- [ ] Hapus file gambar lama saat upload baru.
- [ ] `DELETE /api/admin/cms/promo/gambar` (idempoten).
- [ ] `GET /api/cms/promo` memakai pin + fallback auto-pick, mengembalikan `badge`,
      `tautan_tujuan`, `url_gambar`, `id_product_peruntukan`, `nama_produk_peruntukan`.
- [ ] Feature test: pin valid, pin invalid/nonaktif → fallback, upload/ganti/hapus gambar,
      `data: null` saat kosong.
- [ ] `./vendor/bin/pint` + `composer test` hijau.
