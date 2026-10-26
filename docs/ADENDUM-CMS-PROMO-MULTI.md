# Adendum API — Multi Promo Beranda (`cms_promo`)

> **Status:** Diimplementasikan di FE (2026-10-23)
> **Menggantikan:** `ADENDUM-CMS-PROMO-CONFIG.md` untuk perilaku multi-promo
> **Relasi:** `list-api.md` §9 (Voucher), §19.2 (`GET /cms/promo`)

## 1. Ringkasan

Banner promo beranda sekarang mendukung **lebih dari satu promo**. Setiap promo menunjuk satu
voucher dan dapat memiliki badge, link tujuan, serta gambar banner sendiri.

Landing menampilkan kode voucher, tombol salin, dan instruksi checkout. Jika ada lebih dari satu
promo, landing menampilkan carousel otomatis.

Admin mengatur daftar promo di **Pengaturan → Kelola Promo**. Voucher tetap dibuat dan diubah di
halaman **Buat Promo**.

## 2. Keputusan

1. Satu voucher hanya boleh dipakai satu promo beranda.
2. Promo yang tampil di landing adalah voucher yang aktif dan berada dalam periode berlaku.
3. Nama dan deskripsi promo mengikuti voucher.
4. Badge dan gambar banner dapat diatur per promo.
5. Link tujuan CTA:
   - voucher khusus satu produk dan link kosong → `/detail/{id_product_peruntukan}`;
   - voucher semua produk dan link kosong → `/katalog`.
6. **Tidak memakai `urutan_tampilan`.** Urutan promo mengikuti urutan respons backend.
7. Menghapus promo hanya menghapus dari beranda; voucher tidak dihapus.
8. Jika tidak ada promo aktif, landing memakai banner statis.

## 3. Endpoint Publik

### `GET /api/cms/promos`

Mengembalikan daftar promo aktif. Backend menentukan urutan.

**Respons 200:**

```json
{
  "success": true,
  "message": "Promo berhasil diambil.",
  "data": [
    {
      "id_cms_promo": 1,
      "id_voucher": "e34db947-e01d-40a3-8646-d3e99c9494fa",
      "badge": "diskon",
      "tautan_tujuan": "/detail/7c8c03b3-9cae-45f4-ab3c-d70b53851fbf",
      "gambar": "cms/promo/example.jpg",
      "url_gambar": "https://backend.example/storage/cms/promo/example.jpg",
      "voucher": {
        "id_voucher": "e34db947-e01d-40a3-8646-d3e99c9494fa",
        "kode_voucher": "ALIPANJAY",
        "nama_voucher": "anjay",
        "deskripsi": "ll",
        "status_voucher": "aktif",
        "tanggal_mulai": "2026-10-21T00:00:00.000000Z",
        "tanggal_selesai": "2026-10-25T00:00:00.000000Z",
        "tipe_diskon": "persentase",
        "nilai_diskon": 10
      }
    }
  ]
}
```

`data` boleh berupa array langsung, objek paginator `{ data: [...] }`, atau satu objek. FE
menormalkan ketiganya.

## 4. Endpoint Admin

| Method | Path | Keterangan |
|---|---|---|
| GET | `/api/admin/cms/promos` | Semua promo, termasuk voucher yang sudah tidak aktif |
| POST | `/api/admin/cms/promos` | Tambah promo |
| PUT | `/api/admin/cms/promos/{id}` | Ubah promo |
| DELETE | `/api/admin/cms/promos/{id}` | Hapus promo dari beranda |

Payload `POST`/`PUT` menggunakan `multipart/form-data`:

| Field | Wajib | Keterangan |
|---|---|---|
| `id_voucher` | Ya | UUID voucher |
| `badge` | Tidak | Maksimal 40 karakter |
| `tautan_tujuan` | Tidak | Relatif atau absolut |
| `gambar` | Tidak | JPG/PNG/WebP, maksimal 2 MB |
| `_method` | PUT | Diisi FE saat update multipart |

FE mengirim update multipart sebagai `POST` + `_method=PUT` karena PHP tidak mem-parsing body
multipart pada method `PUT`.

## 5. Kompatibilitas FE

- Landing memakai `/api/cms/promos`.
- Jika endpoint tersebut gagal atau kosong, landing mencoba `/api/cms/promo` (single promo).
- Jika keduanya gagal atau tidak ada data, landing menampilkan banner statis.
- `imgUrl()` memproksi `cms/promo/*` ke `/media/cms-promo/*`.

## 6. Catatan Backend

Saat adendum ini dibuat, `GET /api/cms/promos` masih mengembalikan `500`:

```text
SQLSTATE[42S22]: Column not found: 1054
Unknown column 'urutan_tampilan' in 'order clause'
```

FE tidak lagi mengirim atau memakai `urutan_tampilan`. Backend perlu menghapus kolom tersebut dari
query dan mengurutkan promo dengan `id_cms_promo` atau `created_at`.
