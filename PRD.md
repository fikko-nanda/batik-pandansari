# Product Requirement Document (PRD)

## E-Commerce & Interactive Atelier Batik Pandansari

* **Versi Dokumen:** 1.2.0
* **Tanggal:** 15 Oktober 2026
* **Status:** Final Baseline Architecture
* **Framework Core:** Laravel 13 (PHP 8.4)
* **Frontend Stack:** Livewire v3, Alpine.js, Tailwind CSS, Blade
* **Admin & Engineering Console:** Filament PHP v3
* **Database Management System:** MySQL 8.x
* **Integrasi Service:** RajaOngkir API, Midtrans Snap & Webhook

---

## 1. Ringkasan Proyek & Tujuan

Proyek **Batik Pandansari** bertujuan untuk membangun platform e-commerce dan profil sanggar (atelier) wastra Nusantara berkelas *enterprise*. Platform ini memadukan kemewahan identitas visual kultural dengan keandalan sistem transaksi digital modern.

### **Tujuan Utama:**
1. Menyediakan sistem transaksi e-commerce wastra batik (Tulis, Cap, Handprint, Sarimbit) yang responsif dan seamless bagi **Customer**.
2. Mempermudah operasional atelier via **Admin Panel** (katalog, stok varian, pesanan, promosi, dan CMS toko).
3. Menyediakan **Engineering Console** bagi **Teknisi** untuk memantau integrasi API, telemetry gateway, dan penanganan webhook real-time.
4. Menjamin konsistensi data transaksi dan pemotongan stok berdasar validasi terpusat di sisi backend.

---

## 2. Arsitektur Sistem & Hak Akses Peran (Roles)

Sistem menggunakan pendekatan **Hybrid Monolith** Laravel 13, dengan pemisahan hak akses dan panel berdasarkan peran pengguna:

```
                               ┌─────────────────────────────────────────┐
                               │        Laravel 13 Application           │
                               └────────────────────┬────────────────────┘
                                                    │
         ┌──────────────────────────────────────────┼──────────────────────────────────────────┐
         ▼                                          ▼                                          ▼
┌─────────────────────────────────┐    ┌─────────────────────────────────┐    ┌─────────────────────────────────┐
│     Customer Public Frontend    │    │      Atelier Backoffice Panel   │    │    Engineering Console Panel    │
│  (Blade + Livewire + Tailwind)  │    │     (Filament v3 - /admin)      │    │    (Filament v3 - /teknisi)    │
└────────────────┬────────────────┘    └────────────────┬────────────────┘    └────────────────┬────────────────┘
                 │                                      │                                      │
                 └──────────────────────────────────────┼──────────────────────────────────────┘
                                                        │
                                                        ▼
                                       ┌─────────────────────────────────┐
                                       │       MySQL 8.x Database        │
                                       └─────────────────────────────────┘
```

### **Otorisasi Akses 3 Role:**
* **Customer:** Akses publik (`/`, `/katalog`, `/checkout`, `/akun`). Hanya dapat membaca/mengubah data milik sendiri.
* **Admin:** Akses Backoffice (`/admin`). Pengelolaan operasional toko, pesanan, inventaris, dan CMS.
* **Teknisi:** Akses Engineering Console (`/teknisi`). Pemantauan log telemetry, webhook status, dan debugging tanpa akses pengubahan data transaksi/pelanggan.

---

## 3. Kebutuhan Fungsional (Functional Requirements)

### **3.1 Modul Customer (Frontend Publik & Dashboard)**
1. **Landing Page:** Banner hero CMS, kategori utama (Batik Tulis, Cap, Handprint), koleksi pilihan, banner promo Sarimbit, dan lokasi galeri fisik (Google Maps).
2. **Katalog & Filter:** Filter kategori, gender (Pria, Wanita, Unisex), dan rentang harga; Pengurutan (Terbaru, Harga Terendah/Tertinggi); Wishlist & Live Pagination.
3. **Detail Produk:** Gallery thumbnail preview, selector varian ukuran (S, M, L, XL, XXL) yang dinamis menampilkan stok & Lingkar Dada/Panjang Baju, serta pemicu WhatsApp Fitting.
4. **Keranjang Belanja & Checkout:** Pengaturan quantity real-time, klaim kupon promo, dropdown wilayah bertingkat (Provinsi -> Kota -> Kecamatan) via RajaOngkir, kalkulasi ongkir ekspedisi (JNE, J&T, POS, SiCepat), dan pembayaran Midtrans Snap.
5. **Akun & Profil:** Profil pelanggan, buku alamat utama/alternatif, ubah password, dan riwayat pesanan (snapshot item, status tracking, resi).

### **3.2 Modul Admin (Atelier Backoffice - `/admin`)**
1. **Dashboard & Finance:** Stat metrics omset, order aktif, rata-rata transaksi, dan laporan pembukuan (omset gross, fee MDR Midtrans, omset net).
2. **Katalog & Stok Varian:** CRUD Master produk, varian SKU, harga, berat, dan fitur *Kurasi Stok Cepat* (inline stock update).
3. **Logistik & Transaksi:** Filter tab pesanan (Menunggu Pembayaran, Diproses, Dikirim, Selesai, Dibatalkan), input resi, dan pencetakan shipping label.
4. **Promosi & CMS:** Pengaturan kode kupon (minimal belanja, diskon max, periode), kelola banner hero, 3 kategori utama, galeri foto atelier, dan direktori pengguna.

### **3.3 Modul Teknisi (Engineering Console - `/teknisi`)**
1. **Atelier Core Relay (Webhook Stream):** Event log real-time dari Midtrans (`payment.settlement`, `payment.expire`) dan RajaOngkir (`shipment.dispatched`). Inspector drawer untuk payload JSON, response, latency, serta aksi *Re-trigger Webhook Manual*.
2. **Gateway Telemetry & Audit Log:** Audit trail HTTP Request/Response (`GET`, `POST`), Telemetry Inspector (Header, Request/Response payload), status WebSocket, dan Replay in Sandbox.

---

## 4. Skema Database MySQL (24 Tabel Lengkap)

### **4.1 Daftar Tabel Berdasarkan Modul**

#### **1. Pengguna & Alamat**
1. **`users`**: Akun pengguna (Customer, Admin, Teknisi).
   * `id` (BIGINT, Primary), `name` (VARCHAR 255), `email` (VARCHAR 255, Unique), `phone_number` (VARCHAR 30, Nullable), `gender` (ENUM 'L','P', Nullable), `birth_date` (DATE, Nullable), `password` (VARCHAR 255), `role` (ENUM 'customer','admin','teknisi', Default 'customer'), `remember_token` (VARCHAR 100, Nullable), `timestamps`.
2. **`user_addresses`**: Buku alamat pengiriman pelanggan.
   * `id` (BIGINT, Primary), `user_id` (FK -> users.id), `label_name` (VARCHAR 100), `recipient_name` (VARCHAR 255), `phone_number` (VARCHAR 30), `province_id` (INT), `province_name` (VARCHAR 100), `city_id` (INT), `city_name` (VARCHAR 100), `subdistrict_id` (INT), `subdistrict_name` (VARCHAR 100), `postal_code` (VARCHAR 10), `full_address` (TEXT), `is_primary` (BOOLEAN, Default false), `timestamps`.

#### **2. Katalog Produk**
3. **`categories`**: Kategori produk batik.
   * `id` (BIGINT, Primary), `name` (VARCHAR 255), `slug` (VARCHAR 255, Unique), `description` (TEXT, Nullable), `image_path` (VARCHAR 255, Nullable), `is_active` (BOOLEAN, Default true), `timestamps`.
4. **`products`**: Data utama mahakarya.
   * `id` (BIGINT, Primary), `category_id` (FK -> categories.id), `name` (VARCHAR 255), `slug` (VARCHAR 255, Unique), `gender_type` (ENUM 'men','women','unisex', Default 'unisex'), `description` (LONGTEXT, Nullable), `material_info` (TEXT, Nullable), `is_featured` (BOOLEAN, Default false), `is_active` (BOOLEAN, Default true), `timestamps`.
5. **`product_variants`**: Varian SKU, harga, dan ukuran.
   * `id` (BIGINT, Primary), `product_id` (FK -> products.id), `sku` (VARCHAR 100, Unique), `size` (ENUM 'S','M','L','XL','XXL','Custom'), `chest_size_cm` (INT, Nullable), `body_length_cm` (INT, Nullable), `price` (DECIMAL 12,2), `stock` (INT, Default 0), `weight_g` (INT, Default 500), `timestamps`.
6. **`product_images`**: Referensi gambar produk.
   * `id` (BIGINT, Primary), `product_id` (FK -> products.id), `image_path` (VARCHAR 255), `sort_order` (INT, Default 0), `is_primary` (BOOLEAN, Default false), `timestamps`.

#### **3. Keranjang & Wishlist**
7. **`carts`**: Keranjang belanja aktif.
   * `id` (BIGINT, Primary), `user_id` (FK -> users.id, Nullable), `session_id` (VARCHAR 255, Nullable), `timestamps`.
8. **`cart_items`**: Detail item di keranjang.
   * `id` (BIGINT, Primary), `cart_id` (FK -> carts.id), `product_variant_id` (FK -> product_variants.id), `quantity` (INT, Default 1), `timestamps`.
9. **`wishlist_items`**: Produk favorit pelanggan.
   * `id` (BIGINT, Primary), `user_id` (FK -> users.id), `product_id` (FK -> products.id), `timestamps`.

#### **4. Inventaris & Stok**
10. **`inventory_movements`**: Log mutasi stok masuk/keluar.
    * `id` (BIGINT, Primary), `product_variant_id` (FK -> product_variants.id), `reference_type` (VARCHAR 100), `reference_id` (BIGINT, Nullable), `type` (ENUM 'in','out','adjustment'), `quantity` (INT), `stock_before` (INT), `stock_after` (INT), `notes` (TEXT, Nullable), `created_at` (TIMESTAMP).
11. **`stock_opnames`**: Catatan penyesuaian fisik stok.
    * `id` (BIGINT, Primary), `product_variant_id` (FK -> product_variants.id), `system_stock` (INT), `physical_stock` (INT), `difference` (INT), `reason` (TEXT), `adjusted_by` (FK -> users.id), `created_at` (TIMESTAMP).

#### **5. Pesanan & Pembayaran**
12. **`orders`**: Data transaksi utama.
    * `id` (BIGINT, Primary), `order_number` (VARCHAR 50, Unique), `user_id` (FK -> users.id), `coupon_id` (FK -> coupons.id, Nullable), `status` (ENUM 'pending','processing','shipped','completed','cancelled', Default 'pending'), `payment_status` (ENUM 'unpaid','paid','expired','failed','refunded', Default 'unpaid'), `subtotal_amount` (DECIMAL 12,2), `discount_amount` (DECIMAL 12,2, Default 0), `shipping_cost` (DECIMAL 12,2), `grand_total` (DECIMAL 12,2), `snap_token` (VARCHAR 255, Nullable), `notes` (TEXT, Nullable), `timestamps`.
13. **`order_items`**: Snapshot detail produk dipesan.
    * `id` (BIGINT, Primary), `order_id` (FK -> orders.id), `product_variant_id` (FK -> product_variants.id), `product_name_snapshot` (VARCHAR 255), `variant_size_snapshot` (VARCHAR 50), `sku_snapshot` (VARCHAR 100), `price` (DECIMAL 12,2), `quantity` (INT), `subtotal` (DECIMAL 12,2), `timestamps`.
14. **`payments`**: Transaksi & rekonsiliasi gateway.
    * `id` (BIGINT, Primary), `order_id` (FK -> orders.id), `transaction_id` (VARCHAR 255, Nullable), `payment_type` (VARCHAR 100, Nullable), `gross_amount` (DECIMAL 12,2), `payment_status` (VARCHAR 50), `payload_response` (JSON, Nullable), `paid_at` (DATETIME, Nullable), `timestamps`.
15. **`order_status_histories`**: Audit trail perubahan status pesanan.
    * `id` (BIGINT, Primary), `order_id` (FK -> orders.id), `status_before` (VARCHAR 50), `status_after` (VARCHAR 50), `changed_by` (FK -> users.id, Nullable), `notes` (TEXT, Nullable), `created_at` (TIMESTAMP).

#### **6. Promosi & Diskon**
16. **`coupons`**: Konfigurasi kode promo.
    * `id` (BIGINT, Primary), `code` (VARCHAR 50, Unique), `type` (ENUM 'fixed','percentage'), `value` (DECIMAL 12,2), `min_spend` (DECIMAL 12,2, Default 0), `max_discount` (DECIMAL 12,2, Nullable), `usage_limit` (INT, Nullable), `used_count` (INT, Default 0), `start_date` (DATETIME, Nullable), `end_date` (DATETIME, Nullable), `is_active` (BOOLEAN, Default true), `timestamps`.
17. **`promotion_usages`**: Riwayat klaim kupon transaksi.
    * `id` (BIGINT, Primary), `coupon_id` (FK -> coupons.id), `order_id` (FK -> orders.id), `user_id` (FK -> users.id), `discount_amount` (DECIMAL 12,2), `created_at` (TIMESTAMP).

#### **7. Pengiriman & Logistik**
18. **`order_shipments`**: Informasi pengiriman & resi.
    * `id` (BIGINT, Primary), `order_id` (FK -> orders.id), `courier_code` (VARCHAR 50), `courier_service` (VARCHAR 100), `tracking_number` (VARCHAR 100, Nullable), `recipient_name` (VARCHAR 255), `phone_number` (VARCHAR 30), `full_address` (TEXT), `shipped_at` (DATETIME, Nullable), `delivered_at` (DATETIME, Nullable), `timestamps`.
19. **`shipment_histories`**: Riwayat perubahan status kurir.
    * `id` (BIGINT, Primary), `order_shipment_id` (FK -> order_shipments.id), `status` (VARCHAR 100), `location` (VARCHAR 255, Nullable), `description` (TEXT, Nullable), `created_at` (TIMESTAMP).
20. **`order_fulfillments`**: Log persiapan & packing barang.
    * `id` (BIGINT, Primary), `order_id` (FK -> orders.id), `fulfilled_by` (FK -> users.id), `status` (ENUM 'packing','ready_for_pickup','picked_up'), `notes` (TEXT, Nullable), `timestamps`.

#### **8. Integrasi & Monitoring**
21. **`webhook_logs`**: Log callback webhook luar.
    * `id` (BIGINT, Primary), `event_id` (VARCHAR 100), `source` (ENUM 'midtrans','rajaongkir','whatsapp'), `event_type` (VARCHAR 100), `order_number` (VARCHAR 50, Nullable), `payload` (JSON), `response_code` (INT, Default 200), `latency_ms` (INT, Default 0), `status` (ENUM '200_ok','retry','failed', Default '200_ok'), `timestamps`.
22. **`api_request_logs`**: Audit trail HTTP request internal/external.
    * `id` (BIGINT, Primary), `method` (VARCHAR 10), `endpoint` (VARCHAR 255), `request_headers` (JSON, Nullable), `request_payload` (JSON, Nullable), `response_payload` (JSON, Nullable), `status_code` (INT), `latency_ms` (INT), `ip_address` (VARCHAR 45, Nullable), `created_at` (TIMESTAMP).
23. **`activity_logs`**: Audit aktivitas internal user admin/teknisi.
    * `id` (BIGINT, Primary), `user_id` (FK -> users.id, Nullable), `action` (VARCHAR 100), `description` (TEXT), `properties` (JSON, Nullable), `created_at` (TIMESTAMP).

#### **9. CMS Website**
24. **`cms`**: Konten dinamis banner, kategori, & galeri.
    * `id` (BIGINT, Primary), `key` (VARCHAR 100, Unique), `group` (ENUM 'hero_banner','category_featured','atelier_gallery','general'), `title` (VARCHAR 255, Nullable), `content` (LONGTEXT, Nullable), `media_path` (VARCHAR 255, Nullable), `sort_order` (INT, Default 0), `is_active` (BOOLEAN, Default true), `timestamps`.

---

## 5. Integrasi Pihak Ketiga & Aturan Keamanan

### **5.1 RajaOngkir API**
* Hit Data Lokasi (Provinsi, Kota, Kecamatan) dan Kalkulasi Ongkir (`POST /cost`).
* Data lokasi disimpan di cache Redis/Laravel Cache selama 30 hari.

### **5.2 Midtrans Snap & Webhook Handling**
* Generate Snap Token dipemicu via Backend (`POST /snap/v1/transactions`).
* Handling Webhook (`POST /api/v1/webhooks/midtrans`) dengan verifikasi `signature_key` (SHA-512) wajib bersifat **Idempotent** untuk menghindari rekonsiliasi ganda.

### **5.3 Aturan Validasi Backend (Strict NFR)**
* **Validasi Terpusat:** Harga, diskon kupon, kalkulasi total, dan pemotongan stok wajib dihitung dan divalidasi penuh di Backend. Frontend tidak diizinkan mengirimkan nominal total bayar.
* **Keamanan Data Log:** Password, token rahasia, dan signature key **dilarang** dicatat dalam bentuk teks terbuka pada `webhook_logs` atau `api_request_logs`.