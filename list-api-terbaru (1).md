# Daftar API Terbaru — Hasil Pengujian

## Ringkasan hasil

**Hasil akhir: 100 endpoint teruji — 100 LULUS, 0 TIDAK LULUS**

| Keterangan | Jumlah |
|---|---:|
| Total endpoint | 100 |
| Lulus | 100 (100%) |
| Tidak lulus | 0 |
| Akses Admin | 63 |
| Akses Customer | 27 |
| Akses Publik | 10 |

Catatan: `GET /api/test-json` adalah rute debug bawaan (closure); rute produksi sebenarnya berjumlah 99 dan seluruhnya teruji.

## Daftar endpoint

| No. | Method | Endpoint | Akses | Kegunaan | Status |
|---:|---|---|---|---|---|
| 1 | POST | `/api/login` | Publik | Login pengguna, menerbitkan token Sanctum | Lulus |
| 2 | POST | `/api/register` | Publik | Registrasi akun pelanggan baru | Lulus |
| 3 | POST | `/api/payments/midtrans/notification` | Publik | Webhook notifikasi status pembayaran dari Midtrans | Lulus |
| 4 | GET | `/api/categories` | Publik | Daftar kategori produk | Lulus |
| 5 | GET | `/api/categories/{}` | Publik | Detail satu kategori | Lulus |
| 6 | GET | `/api/products` | Publik | Daftar katalog produk (pencarian/filter) | Lulus |
| 7 | GET | `/api/products/{}` | Publik | Detail produk beserta varian | Lulus |
| 8 | GET | `/api/products/{}/images` | Publik | Daftar gambar produk | Lulus |
| 9 | GET | `/api/products/{}/variants` | Publik | Daftar varian produk | Lulus |
| 10 | GET | `/api/test-json` | Publik | Rute debug bawaan (uji respons JSON) | Lulus |
| 11 | GET | `/api/me` | Customer | Profil pengguna yang sedang login | Lulus |
| 12 | PUT | `/api/me` | Customer | Perbarui profil pengguna | Lulus |
| 13 | PUT | `/api/me/password` | Customer | Ubah kata sandi | Lulus |
| 14 | POST | `/api/logout` | Customer | Logout, mencabut token aktif | Lulus |
| 15 | GET | `/api/addresses` | Customer | Daftar alamat pengiriman milik pengguna | Lulus |
| 16 | POST | `/api/addresses` | Customer | Tambah alamat pengiriman | Lulus |
| 17 | GET | `/api/addresses/{}` | Customer | Detail alamat pengiriman | Lulus |
| 18 | PUT | `/api/addresses/{}` | Customer | Perbarui alamat pengiriman | Lulus |
| 19 | DELETE | `/api/addresses/{}` | Customer | Hapus alamat pengiriman | Lulus |
| 20 | GET | `/api/wishlist` | Customer | Daftar produk favorit pengguna | Lulus |
| 21 | POST | `/api/wishlist` | Customer | Tambah produk ke wishlist | Lulus |
| 22 | DELETE | `/api/wishlist/{}` | Customer | Hapus produk dari wishlist | Lulus |
| 23 | GET | `/api/cart` | Customer | Lihat isi keranjang belanja | Lulus |
| 24 | DELETE | `/api/cart` | Customer | Kosongkan keranjang belanja | Lulus |
| 25 | POST | `/api/cart/items` | Customer | Tambah item ke keranjang | Lulus |
| 26 | PUT | `/api/cart/items/{}` | Customer | Ubah jumlah item keranjang | Lulus |
| 27 | DELETE | `/api/cart/items/{}` | Customer | Hapus item dari keranjang | Lulus |
| 28 | POST | `/api/checkout` | Customer | Proses checkout keranjang menjadi pesanan | Lulus |
| 29 | GET | `/api/orders` | Customer | Daftar pesanan milik pengguna | Lulus |
| 30 | GET | `/api/orders/{}` | Customer | Detail pesanan pengguna | Lulus |
| 31 | POST | `/api/orders/{}/payment` | Customer | Buat pembayaran (transaksi Midtrans) untuk pesanan | Lulus |
| 32 | GET | `/api/orders/{}/payment` | Customer | Lihat status pembayaran pesanan | Lulus |
| 33 | GET | `/api/shipping/destinations` | Customer | Cari tujuan pengiriman (RajaOngkir) | Lulus |
| 34 | POST | `/api/shipping/cost` | Customer | Hitung ongkos kirim | Lulus |
| 35 | GET | `/api/vouchers/available` | Customer | Daftar voucher yang bisa dipakai | Lulus |
| 36 | POST | `/api/vouchers/validate` | Customer | Validasi voucher sebelum checkout | Lulus |
| 37 | GET | `/api/voucher-usages/me` | Customer | Riwayat pemakaian voucher pengguna | Lulus |
| 38 | GET | `/api/admin/dashboard/summary` | Admin | Ringkasan metrik dashboard admin | Lulus |
| 39 | GET | `/api/admin/dashboard/revenue` | Admin | Statistik pendapatan (grafik per periode) | Lulus |
| 40 | GET | `/api/admin/dashboard/orders` | Admin | Statistik jumlah pesanan per status & tren | Lulus |
| 41 | GET | `/api/admin/dashboard/aov` | Admin | Average Order Value (nilai rata-rata pesanan) | Lulus |
| 42 | GET | `/api/admin/inventory` | Admin | Daftar inventaris varian produk | Lulus |
| 43 | GET | `/api/admin/inventory/{}` | Admin | Detail inventaris satu varian | Lulus |
| 44 | GET | `/api/admin/inventory/movements` | Admin | Riwayat pergerakan stok (masuk/keluar) | Lulus |
| 45 | GET | `/api/admin/inventory/low-stock` | Admin | Daftar varian berstok kritis/menipis | Lulus |
| 46 | GET | `/api/admin/inventory/summary` | Admin | Ringkasan status stok keseluruhan | Lulus |
| 47 | GET | `/api/admin/inventory/valuation` | Admin | Nilai total persediaan (berdasarkan harga modal) | Lulus |
| 48 | GET | `/api/admin/inventory/export` | Admin | Ekspor data inventaris ke CSV | Lulus |
| 49 | POST | `/api/admin/inventory/adjustments` | Admin | Penyesuaian stok manual (koreksi masuk/keluar) | Lulus |
| 50 | GET | `/api/admin/stock-opnames` | Admin | Daftar sesi stock opname | Lulus |
| 51 | POST | `/api/admin/stock-opnames` | Admin | Buat sesi stock opname baru (hitung selisih) | Lulus |
| 52 | GET | `/api/admin/stock-opnames/{}` | Admin | Detail sesi stock opname | Lulus |
| 53 | GET | `/api/admin/reports/summary` | Admin | Ringkasan laporan keuangan | Lulus |
| 54 | GET | `/api/admin/reports/transactions` | Admin | Buku kas/daftar transaksi pembayaran | Lulus |
| 55 | GET | `/api/admin/reports/transactions/{}` | Admin | Detail satu transaksi | Lulus |
| 56 | GET | `/api/admin/reports/export/pdf` | Admin | Ekspor laporan keuangan ke PDF | Lulus |
| 57 | POST | `/api/admin/categories` | Admin | Tambah kategori produk | Lulus |
| 58 | PUT | `/api/admin/categories/{}` | Admin | Perbarui kategori produk | Lulus |
| 59 | DELETE | `/api/admin/categories/{}` | Admin | Hapus kategori produk | Lulus |
| 60 | GET | `/api/admin/products` | Admin | Daftar produk (tampilan admin, termasuk non-aktif) | Lulus |
| 61 | POST | `/api/products` | Admin | Tambah produk baru | Lulus |
| 62 | GET | `/api/admin/products/{}` | Admin | Detail produk (tampilan admin) | Lulus |
| 63 | PUT | `/api/products/{}` | Admin | Perbarui produk | Lulus |
| 64 | DELETE | `/api/products/{}` | Admin | Hapus produk | Lulus |
| 65 | GET | `/api/admin/products/export` | Admin | Ekspor data produk ke CSV | Lulus |
| 66 | POST | `/api/products/{}/images` | Admin | Unggah gambar produk | Lulus |
| 67 | DELETE | `/api/images/{}` | Admin | Hapus gambar produk | Lulus |
| 68 | POST | `/api/products/{}/variants` | Admin | Tambah varian produk | Lulus |
| 69 | PUT | `/api/variants/{}` | Admin | Perbarui varian produk | Lulus |
| 70 | DELETE | `/api/variants/{}` | Admin | Hapus varian produk | Lulus |
| 71 | GET | `/api/users` | Admin | Daftar pengguna | Lulus |
| 72 | POST | `/api/users` | Admin | Tambah pengguna baru | Lulus |
| 73 | GET | `/api/users/{}` | Admin | Detail pengguna | Lulus |
| 74 | GET | `/api/admin/users/summary` | Admin | Ringkasan statistik pengguna | Lulus |
| 75 | GET | `/api/admin/users/export` | Admin | Ekspor data pengguna ke CSV | Lulus |
| 76 | GET | `/api/admin/orders` | Admin | Daftar seluruh pesanan (tampilan admin) | Lulus |
| 77 | GET | `/api/admin/orders/summary` | Admin | Ringkasan statistik pesanan | Lulus |
| 78 | GET | `/api/admin/orders/{}` | Admin | Detail pesanan (tampilan admin) | Lulus |
| 79 | PUT | `/api/admin/orders/{}/status` | Admin | Ubah status pesanan | Lulus |
| 80 | POST | `/api/admin/orders/{}/fulfillments` | Admin | Buat data pemenuhan/pengiriman pesanan | Lulus |
| 81 | GET | `/api/admin/order-fulfillments` | Admin | Daftar data pemenuhan pesanan | Lulus |
| 82 | GET | `/api/admin/payments` | Admin | Daftar seluruh pembayaran | Lulus |
| 83 | GET | `/api/admin/payments/summary` | Admin | Ringkasan statistik pembayaran | Lulus |
| 84 | GET | `/api/admin/shipments` | Admin | Daftar pengiriman | Lulus |
| 85 | PUT | `/api/admin/shipments/{}/status` | Admin | Perbarui status pengiriman | Lulus |
| 86 | GET | `/api/admin/shipping/config` | Admin | Lihat konfigurasi pengiriman | Lulus |
| 87 | PUT | `/api/admin/shipping/config` | Admin | Perbarui konfigurasi pengiriman | Lulus |
| 88 | GET | `/api/admin/shipping/couriers` | Admin | Daftar kurir yang tersedia | Lulus |
| 89 | PUT | `/api/admin/shipping/couriers/{}` | Admin | Aktifkan/nonaktifkan kurir | Lulus |
| 90 | GET | `/api/admin/shipping/health` | Admin | Cek kesehatan integrasi layanan pengiriman | Lulus |
| 91 | POST | `/api/admin/shipping/simulate` | Admin | Simulasi perhitungan ongkir | Lulus |
| 92 | GET | `/api/vouchers` | Admin | Daftar voucher | Lulus |
| 93 | POST | `/api/vouchers` | Admin | Tambah voucher baru | Lulus |
| 94 | GET | `/api/vouchers/{}` | Admin | Detail voucher | Lulus |
| 95 | PUT | `/api/vouchers/{}` | Admin | Perbarui voucher | Lulus |
| 96 | DELETE | `/api/vouchers/{}` | Admin | Hapus voucher | Lulus |
| 97 | POST | `/api/vouchers/{}/publish` | Admin | Publikasikan voucher | Lulus |
| 98 | POST | `/api/vouchers/{}/unpublish` | Admin | Batalkan publikasi voucher | Lulus |
| 99 | GET | `/api/vouchers/summary` | Admin | Ringkasan statistik voucher | Lulus |
| 100 | GET | `/api/voucher-usages` | Admin | Daftar riwayat pemakaian voucher | Lulus |

## Catatan pengujian

Daftar dan status di atas disusun berdasarkan hasil pengujian yang diberikan. Dokumen ini tidak menyatakan bahwa pengujian dijalankan ulang saat file dibuat.
