# List API Terbaru Batik Pandansari

Dokumentasi ini berisi daftar route API terbaru berdasarkan `routes/api.php`.  
Prefix default: `/api/*` (tergantung konfigurasi server).

> Terakhir diperbarui: 2026-10-28  
> Catatan: Beberapa route admin terkait tabel yang sudah dihapus (`order_fulfillments`, `shipments`, `shipment_histists`, `stock_opnames`, `gambar_ukuran`) masih ada di route list dan perlu dicek kebenarannya.

## 1. Publik (Tanpa Auth)

| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `GET` | `/api/categories` | `Public\CategoryController@index` | List kategori produk |
| `GET` | `/api/products` | `Public\ProductController@index` | List produk (katalog) |
| `GET` | `/api/products/{slug}` | `Public\ProductController@show` | Detail produk by slug |
| `GET` | `/api/products/{product}/variants` | `Public\ProductVariantController@index` | List varian produk |
| `GET` | `/api/products/{product}/images` | `Public\ProductImageController@index` | List gambar produk |
| `GET` | `/api/cms/homepage` | `Public\CmsHomepageController@homepage` | Data homepage (gabungan CMS) |
| `GET` | `/api/cms/promos` | `Public\CmsHomepageController@promos` | List promo aktif |
| `GET` | `/api/cms/promo` | `Public\CmsHomepageController@promo` | Promo tunggal (legacy) |
| `GET` | `/api/cms/hero` | `Public\CmsHeroController@index` | Slide hero homepage |
| `GET` | `/api/cms/kategori` | `Public\CmsKategoriController@index` | Kategori highlight homepage |
| `GET` | `/api/cms/galeri` | `Public\CmsGaleriController@index` | Galeri homepage |
| `GET` | `/api/cms/profil-usaha` | `Public\CmsProfilUsahaController@index` | Info profil usaha |
| `GET` | `/api/cms/fasilitas` | `Public\CmsFasilitasController@index` | List fasilitas usaha |
| `GET` | `/api/gambar-ukuran` | `Public\GambarUkuranController@index` | Tabel ukuran produk (publik) |

## 2. Auth, Registrasi & Lupa Password (Publik)

| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `POST` | `/api/register` | `Customer\AuthController@register` | Registrasi akun baru |
| `POST` | `/api/login` | `Customer\AuthController@login` | Login email + password |
| `POST` | `/api/auth/google/redirect` | `Customer\GoogleAuthController@redirect` | Redirect ke Google OAuth |
| `GET\|POST` | `/api/auth/google/callback` | `Customer\GoogleAuthController@callback` | Callback Google OAuth |
| `POST` | `/api/otp/verify-registration` | `Customer\OtpController@verifyRegistration` | Verifikasi OTP registrasi |
| `POST` | `/api/otp/resend-registration` | `Customer\OtpController@resendRegistration` | Kirim ulang OTP registrasi |
| `POST` | `/api/forgot-password` | `Customer\OtpController@forgotPassword` | Request reset password (kirim OTP) |
| `POST` | `/api/forgot-password/verify-otp` | `Customer\OtpController@forgotPasswordVerifyOtp` | Verifikasi OTP lupa password |
| `POST` | `/api/forgot-password/resresend-otp` | `Customer\OtpController@forgotPasswordResendOtp` | Kirim ulang OTP lupa password |
| `POST` | `/api/forgot-password/reset` | `Customer\OtpController@forgotPasswordReset` | Ganti password pakai `reset_token` custom |

## 3. Customer (`auth:sanctum`)

| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `POST` | `/api/logout` | `Customer\AuthController@logout` | Logout (revoke token) |
| `GET` | `/api/me` | `Customer\AuthController@me` | Profil user yang sedang login |
| `GET` | `/api/cart` | `Customer\CartController@index` | Lihat keranjang |
| `POST` | `/api/cart/items` | `Customer\CartController@addItem` | Tambah item ke keranjang |
| `PUT` | `/api/cart/items/{itemId}` | `Customer\CartController@updateItem` | Update jumlah item keranjang |
| `DELETE` | `/api/cart/items/{itemId}` | `Customer\CartController@removeItem` | Hapus item keranjang |
| `DELETE` | `/api/cart` | `Customer\CartController@clear` | Kosongkan keranjang |
| `GET` | `/api/addresses` | `Customer\AddressController@index` | List alamat user |
| `POST` | `/api/addresses` | `Customer\AddressController@store` | Tambah alamat baru |
| `PUT` | `/api/addresses/{id}` | `Customer\AddressController@update` | Update alamat |
| `DELETE` | `/api/addresses/{id}` | `Customer\AddressController@destroy` | Hapus alamat |
| `PUT` | `/api/addresses/{id}/primary` | `Customer\AddressController@setPrimary` | Jadikan alamat utama |
| `GET` | `/api/wishlist` | `Customer\WishlistController@index` | List wishlist |
| `POST` | `/api/wishlist` | `Customer\WishlistController@toggle` | Tambah/hapus wishlist |
| `DELETE` | `/api/wishlist/{productId}` | `Customer\WishlistController@destroy` | Hapus dari wishlist |
| `POST` | `/api/checkout` | `Customer\CheckoutController@checkout` | Proses checkout & buat order |
| `GET` | `/api/orders` | `Customer\OrderController@index` | Riwayat pesanan user |
| `GET` | `/api/orders/{id}` | `Customer\OrderController@show` | Detail pesanan |
| `POST` | `/api/orders/{id}/cancel` | `Customer\OrderController@cancel` | Batalkan pesanan (jika masih bisa dibatalkan) |
| `POST` | `/api/payments/{orderId}/create` | `Customer\PaymentController@create` | Generate link pembayaran Midtrans |
| `GET` | `/api/payments/{orderId}/status` | `Customer\PaymentController@status` | Cek status pembayaran |
| `POST` | `/api/vouchers/apply` | `Customer\VoucherController@apply` | Cek & apply voucher ke keranjang/order |
| `GET` | `/api/vouchers/available` | `Customer\VoucherController@available` | List voucher yang bisa dipakai user |

## 4. Admin (`auth:sanctum`, `admin`)

### Produk, Kategori & Media
| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `GET` | `/api/admin/products` | `Admin\ProductController@adminIndex` | List produk (admin) |
| `GET` | `/api/admin/products/export` | `Admin\ProductController@adminExport` | Export produk (admin) |
| `GET` | `/api/admin/products/{id}` | `Admin\ProductController@adminShow` | Detail produk (admin) |
| `POST` | `/api/products` | `Admin\ProductController@store` | Tambah produk (admin) |
| `PUT` | `/api/products/{id}` | `Admin\ProductController@update` | Update produk (admin) |
| `DELETE` | `/api/products/{id}` | `Admin\ProductController@destroy` | Hapus produk (admin) |
| `POST` | `/api/products/{id}/variants` | `Admin\ProductVariantController@store` | Tambah varian produk (admin) |
| `POST` | `/api/admin/products/{id}/variants/bulk` | `Admin\ProductVariantController@bulkStore` | Tambah varian secara massal (admin) |
| `PUT` | `/api/variants/{id}` | `Admin\ProductVariantController@update` | Update varian (admin) |
| `DELETE` | `/api/variants/{id}` | `Admin\ProductVariantController@destroy` | Hapus varian (admin) |
| `GET` | `/api/admin/products/{id}/images` | `Admin\ProductImageController@adminIndex` | List gambar produk (admin) |
| `POST` | `/api/products/{id}/images` | `Admin\ProductImageController@store` | Upload gambar produk (admin) |
| `POST` | `/api/admin/products/{id}/images` | `Admin\ProductImageController@store` | Upload gambar (scope admin) |
| `DELETE` | `/api/images/{id}` | `Admin\ProductImageController@destroy` | Hapus gambar (admin) |
| `DELETE` | `/api/admin/products/{id}/images/{imageId}` | `Admin\ProductImageController@destroyScoped` | Hapus gambar (scope admin) |
| `POST` | `/api/admin/categories` | `Admin\CategoryController@store` | Tambah kategori (admin) |
| `PUT` | `/api/admin/categories/{id}` | `Admin\CategoryController@update` | Update kategori (admin) |
| `DELETE` | `/api/admin/categories/{id}` | `Admin\CategoryController@destroy` | Hapus kategori (admin) |

### Voucher
| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `GET` | `/api/vouchers` | `Admin\VoucherController@index` | List voucher (admin) |
| `POST` | `/api/vouchers` | `Admin\VoucherController@store` | Tambah voucher (admin) |
| `GET` | `/api/vouchers/summary` | `Admin\VoucherController@summary` | Ringkasan voucher (admin) |
| `GET` | `/api/vouchers/{id}` | `Admin\VoucherController@show` | Detail voucher (admin) |
| `PUT` | `/api/vouchers/{id}` | `Admin\VoucherController@update` | Update voucher (admin) |
| `DELETE` | `/api/vouchers/{id}` | `Admin\VoucherController@destroy` | Hapus voucher (admin) |
| `POST` | `/api/vouchers/{id}/publish` | `Admin\VoucherController@publish` | Aktifkan voucher (admin) |
| `POST` | `/api/vouchers/{id}/unpublish` | `Admin\VoucherController@unpublish` | Nonaktifkan voucher (admin) |
| `DELETE` | `/api/vouchers/{id}/gambar` | `Admin\VoucherController@destroyImage` | Hapus gambar voucher (admin) |
| `GET` | `/api/voucher-usages` | `Admin\VoucherUsageController@index` | Riwayat penggunaan voucher (admin) |

### Pesanan, Pembayaran & Pengiriman
| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `GET` | `/api/admin/orders` | `Admin\OrderController@adminIndex` | List semua pesanan (admin) |
| `GET` | `/api/admin/orders/summary` | `Admin\OrderController@adminSummary` | Ringkansan pesanan (admin) |
| `GET` | `/api/admin/orders/{id}` | `Admin\OrderController@adminShow` | Detail pesanan (admin) |
| `PUT` | `/api/admin/orders/{id}/status` | `Admin\OrderController@updateStatus` | Update status pesanan (admin) |
| `GET` | `/api/admin/payments` | `Admin\PaymentController@adminIndex` | List pembayaran (admin) |
| `GET` | `/api/admin/payments/summary` | `Admin\PaymentController@adminSummary` | Ringkasan pembayaran (admin) |
| `POST` | `/api/admin/orders/{id}/fulfillments` | `Admin\FulfillmentController@store` | Tambah riwayat fulfillment (admin) |
| `GET` | `/api/admin/order-fulfillments` | `Admin\FulfillmentController@index` | List fulfillment (admin) |
| `GET` | `/api/admin/shipments` | `Admin\ShipmentController@index` | List shipment (admin) - *Route masih ada (tabel sudah dihapus)* |
| `PUT` | `/api/admin/shipments/{id}/status` | `Admin\ShipmentController@updateStatus` | Update status shipment (admin) - *Route masih ada (tabel sudah dihapus)* |

### Inventory & Stock Opname
| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `GET` | `/api/admin/inventory` | `Admin\AdminInventoryController@index` | Stok per varian (admin) |
| `GET` | `/api/admin/inventory/summary` | `Admin\AdminInventoryController@summary` | Ringkasan stok (admin) |
| `GET` | `/api/admin/inventory/low-stock` | `Admin\AdminInventoryController@lowStock` | Stok menipis (admin) |
| `GET` | `/api/admin/inventory/valuation` | `Admin\AdminInventoryController@valuation` | Valuasi stok (admin) |
| `GET` | `/api/admin/ininventory/movements` | `Admin\AdminInventoryController@movements` | Riwayat pergerakan stok (admin) |
| `GET` | `/api/admin/inventory/export` | `Admin\AdminInventoryController@export` | Export stok (admin) |
| `GET` | `/api/admin/inventory/{variant_id}` | `Admin\AdminInventoryController@show` | Detail stok per varian (admin) |
| `POST` | `/api/admin/inventory/adjustments` | `Admin\AdminInventoryController@adjust` | Penyesuaian stok manual (admin) |
| `GET` | `/api/admin/stock-opnames` | `Admin\AdminStockOpnameController@index` | List stock opname (admin) - *Route masih ada (tabel sudah dihapus)* |
| `POST` | `/api/admin/stock-opnames` | `Admin\AdminStockOpnameController@store` | Buat stock opname (admin) - *Route masih ada (tabel sudah dihapus)* |
| `GET` | `/api/admin/stock-opnames/{id}` | `Admin\AdminStockOpnameController@show` | Detail stock opname (admin) - *Route masih ada (tabel sudah dihapus)* |
| `POST` | `/api/admin/stock-opnames/{id}/items` | `Admin\AdminStockOpNameController@storeItem` | Input hasil hitung fisik (admin) - *Route masih ada (tabel sudah dihapus)* |
| `POST` | `/api/admin/stock-opnames/{id}/complete` | `Admin\AdminStockOpnameController@complete` | Finalisasi stock opname (admin) - *Route masih ada (tabel sudah dihapus)* |

### CMS Admin, Users, Dashboard & Laporan
| Method | Endpoint | Controller@Action | Keterangan |
|---|---|---|---|
| `GET` | `/api/admin/cms/hero` | `Admin\CmsHeroController@adminIndex` | List slide hero (admin) |
| `POST` | `/api/admin/cms/hero` | `Admin\CmsHeroController@store` | Tambah slide hero (admin) |
| `PUT` | `/api/admin/cms/hero/{id}` | `Admin\CmsHeroController@update` | Update slide hero (admin) |
| `DELETE` | `/api/admin/cms/hero/{id}` | `Admin\CmsHeroController@destroy` | Hapus slide hero (admin) |
| `GET` | `/api/admin/cms/kategori` | `Admin\CmsKategoriController@adminIndex` | List kategori highlight CMS (admin) |
| `POST` | `/api/admin/cms/kategori` | `Admin\CmsKategoriController@store` | Tambah kategori highlight (admin) |
| `PUT` | `/api/admin/cms/kategori/{id}` | `Admin\CmsKategoriController@update` | Update kategori highlight (admin) |
| `DELETE` | `/api/admin/cms/kategori/{id}` | `Admin\CmsKategoriController@destroy` | Hapus kategori highlight (admin) |
| `GET` | `/api/admin/cms/galeri` | `Admin\CmsGaleriController@adminShow` | Pengaturan galeri (admin) |
| `PUT` | `/api/admin/cms/galeri` | `Admin\CmsGaleriController@update` | Update pengaturan galeri (admin) |
| `POST` | `/api/admin/cms/galeri/gambar` | `Admin\CmsGaleriController@storeImage` | Tambah gambar galeri (admin) |
| `PUT` | `/api/admin/cms/galeri/gambar/{id}` | `Admin\CmsGaleriController@updateImage` | Update gambar galeri (admin) |
| `DELETE` | `/api/admin/cms/galeri/gambar/{id}` | `Admin\CmsGaleriController@destroyImage` | Hapus gambar galeri (admin) |
| `GET` | `/api/admin/cms/profil-usaha` | `Admin\CmsProfilUsahaController@index` | Lihat profil usaha (admin) |
| `PUT` | `/api/admin/cns/profil-usaha` | `Admin\CmsProfilUsahaController@update'   * typo in route/path name expected?*/}</td>
                </tr>
                <tr>
                    <td>PUT</td>
                    <td>/api/admin/cms/profil-usaha</td>
                    <td>Admin\CmsProfilUsahaController@update</td>
                    <td>Update profil usaha (admin)</td>
                </tr>
                <tr>
                    <td>GET</td>
                    <td>/api/admin/cms/fasilitas</td>
        </tr>
        <tr>
            <td>GET</td>
            <td>"/api/admin/cms/fasilitas"</td>
            <td>Admin\CmsFasilitasController@adminIndex</td>
            <td>Keterangan: List fasilitas (admin)</td>
        </tr>
    </tbody>
</table>...
