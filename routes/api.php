<?php

use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminInventoryController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminStockOpnameController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CmsFasilitasController;
use App\Http\Controllers\Api\Admin\CmsGaleriController;
use App\Http\Controllers\Api\Admin\CmsHeroController;
use App\Http\Controllers\Api\Admin\CmsKategoriController;
use App\Http\Controllers\Api\Admin\CmsProfilUsahaController;
use App\Http\Controllers\Api\Admin\CmsPromoController;
use App\Http\Controllers\Api\Admin\FulfillmentController;
use App\Http\Controllers\Api\Admin\GambarUkuranController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Admin\ShipmentController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\Admin\VoucherUsageController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\AuthController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\GoogleAuthController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\Customer\VoucherController as CustomerVoucherController;
use App\Http\Controllers\Api\Customer\VoucherUsageController as CustomerVoucherUsageController;
use App\Http\Controllers\Api\Customer\WishlistController;
use App\Http\Controllers\Api\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\Public\CmsFasilitasController as PublicCmsFasilitasController;
use App\Http\Controllers\Api\Public\CmsGaleriController as PublicCmsGaleriController;
use App\Http\Controllers\Api\Public\CmsHeroController as PublicCmsHeroController;
use App\Http\Controllers\Api\Public\CmsHomepageController;
use App\Http\Controllers\Api\Public\CmsKategoriController as PublicCmsKategoriController;
use App\Http\Controllers\Api\Public\CmsProfilUsahaController as PublicCmsProfilUsahaController;
use App\Http\Controllers\Api\Public\GambarUkuranController as PublicGambarUkuranController;
use App\Http\Controllers\Api\Public\MidtransNotificationController;
use App\Http\Controllers\Api\Public\ProductController as PublicProductController;
use App\Http\Controllers\Api\Public\ProductImageController as PublicProductImageController;
use App\Http\Controllers\Api\Public\ProductVariantController as PublicProductVariantController;
use App\Http\Controllers\Api\Teknisi\TeknisiController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth:sanctum', 'admin']);
Route::post('/users', [UserController::class, 'store'])
    ->middleware(['auth:sanctum', 'admin']);
Route::get('/users/{id}', [UserController::class, 'show'])
    ->middleware(['auth:sanctum', 'admin']);

// API Autentikasi
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
// Login/daftar lewat ID Token Google (publik, dibatasi per IP).
Route::post('/auth/google', [GoogleAuthController::class, 'login'])
    ->middleware('throttle:google-auth');
// Konfigurasi publik Google Sign-In (Client ID untuk browser).
Route::get('/auth/google/config', [GoogleAuthController::class, 'config']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    // Tautkan akun Google ke akun login (metode lama dulu).
    Route::post('/me/google', [GoogleAuthController::class, 'linkGoogle'])
        ->middleware('throttle:google-auth');

    // API Alamat (login, hanya milik sendiri)
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{id}', [AddressController::class, 'show']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    // API Wishlist (login, hanya milik sendiri)
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);

    // API Keranjang (login, hanya milik sendiri)
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::put('/cart/items/{id}', [CartController::class, 'update']);
    Route::delete('/cart/items/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // API Voucher untuk pelanggan (login)
    Route::get('/vouchers/available', [CustomerVoucherController::class, 'available']);
    Route::post('/vouchers/validate', [CustomerVoucherController::class, 'validateVoucher']);
    Route::get('/voucher-usages/me', [CustomerVoucherUsageController::class, 'mine']);

    // API Checkout & Pesanan pelanggan (login, hanya milik sendiri)
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::get('/orders', [CustomerOrderController::class, 'index']);
    Route::get('/orders/{id}', [CustomerOrderController::class, 'show']);
    Route::get('/orders/{id}/invoice', [CustomerOrderController::class, 'invoice']);

    // API Pembayaran pelanggan (login, hanya milik sendiri)
    Route::post('/orders/{id}/payment', [CustomerPaymentController::class, 'store']);
    Route::get('/orders/{id}/payment', [CustomerPaymentController::class, 'show']);
});

// API Kategori
Route::get('/categories', [PublicCategoryController::class, 'index']);
Route::get('/categories/{id}', [PublicCategoryController::class, 'show']);

// API Produk (baca: publik)
Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{id}', [PublicProductController::class, 'show']);

// API Varian & Gambar Produk (baca: publik)
Route::get('/products/{id}/variants', [PublicProductVariantController::class, 'index']);
Route::get('/products/{id}/images', [PublicProductImageController::class, 'index']);

// API Konten CMS (baca: publik)
Route::get('/cms/homepage', [CmsHomepageController::class, 'homepage']);
Route::get('/cms/promos', [CmsHomepageController::class, 'promos']);
Route::get('/cms/promo', [CmsHomepageController::class, 'promo']);
Route::get('/cms/hero', [PublicCmsHeroController::class, 'index']);
Route::get('/cms/kategori', [PublicCmsKategoriController::class, 'index']);
Route::get('/cms/galeri', [PublicCmsGaleriController::class, 'index']);
Route::get('/cms/profil-usaha', [PublicCmsProfilUsahaController::class, 'index']);
Route::get('/cms/fasilitas', [PublicCmsFasilitasController::class, 'index']);

// API Gambar Panduan Ukuran (baca: publik, kelola: admin)
Route::get('/gambar-ukuran', [PublicGambarUkuranController::class, 'index']);

// API Produk (tulis: admin)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/products', [ProductController::class, 'adminIndex']);
    // Ekspor harus didaftarkan sebelum wildcard /admin/products/{id}.
    Route::get('/admin/products/export', [ProductController::class, 'adminExport']);
    Route::get('/admin/products/{id}', [ProductController::class, 'adminShow']);

    // API Varian & Gambar (tulis: admin, jalur /api/admin/...)
    Route::post('/admin/products/{id}/variants/bulk', [ProductVariantController::class, 'bulkStore']);
    Route::get('/admin/products/{id}/images', [ProductImageController::class, 'adminIndex']);
    Route::post('/admin/products/{id}/images', [ProductImageController::class, 'store']);
    Route::delete('/admin/products/{id}/images/{imageId}', [ProductImageController::class, 'destroyScoped']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // API Varian & Gambar (tulis: admin)
    Route::post('/products/{id}/variants', [ProductVariantController::class, 'store']);
    Route::put('/variants/{id}', [ProductVariantController::class, 'update']);
    Route::delete('/variants/{id}', [ProductVariantController::class, 'destroy']);
    Route::post('/products/{id}/images', [ProductImageController::class, 'store']);
    Route::delete('/images/{id}', [ProductImageController::class, 'destroy']);

    // API Voucher & Penggunaan Voucher (admin)
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::post('/vouchers', [VoucherController::class, 'store']);
    // Ringkasan & publikasi didaftarkan sebelum /vouchers/{id} agar tidak
    // tertangkap wildcard parameternya.
    Route::get('/vouchers/summary', [VoucherController::class, 'summary']);
    Route::post('/vouchers/{id}/publish', [VoucherController::class, 'publish']);
    Route::post('/vouchers/{id}/unpublish', [VoucherController::class, 'unpublish']);
    Route::get('/vouchers/{id}', [VoucherController::class, 'show']);
    Route::put('/vouchers/{id}', [VoucherController::class, 'update']);
    Route::delete('/vouchers/{id}', [VoucherController::class, 'destroy']);
    Route::get('/voucher-usages', [VoucherUsageController::class, 'index']);

    // API Konten CMS (tulis: admin)
    Route::get('/admin/cms/hero', [CmsHeroController::class, 'adminIndex']);
    Route::post('/admin/cms/hero', [CmsHeroController::class, 'store']);
    Route::put('/admin/cms/hero/{id}', [CmsHeroController::class, 'update']);
    Route::delete('/admin/cms/hero/{id}', [CmsHeroController::class, 'destroy']);

    Route::get('/admin/cms/kategori', [CmsKategoriController::class, 'adminIndex']);
    Route::post('/admin/cms/kategori', [CmsKategoriController::class, 'store']);
    Route::put('/admin/cms/kategori/{id}', [CmsKategoriController::class, 'update']);
    Route::delete('/admin/cms/kategori/{id}', [CmsKategoriController::class, 'destroy']);

    // Rute statis /galeri didaftarkan sebelum wildcard /galeri/gambar/{id}.
    Route::get('/admin/cms/galeri', [CmsGaleriController::class, 'adminShow']);
    Route::put('/admin/cms/galeri', [CmsGaleriController::class, 'update']);
    Route::post('/admin/cms/galeri/gambar', [CmsGaleriController::class, 'storeImage']);
    Route::put('/admin/cms/galeri/gambar/{id}', [CmsGaleriController::class, 'updateImage']);
    Route::delete('/admin/cms/galeri/gambar/{id}', [CmsGaleriController::class, 'destroyImage']);

    Route::get('/admin/cms/profil-usaha', [CmsProfilUsahaController::class, 'index']);
    Route::put('/admin/cms/profil-usaha', [CmsProfilUsahaController::class, 'update']);

    Route::get('/admin/cms/fasilitas', [CmsFasilitasController::class, 'adminIndex']);
    Route::post('/admin/cms/fasilitas', [CmsFasilitasController::class, 'store']);
    Route::put('/admin/cms/fasilitas/{id}', [CmsFasilitasController::class, 'update']);
    Route::delete('/admin/cms/fasilitas/{id}', [CmsFasilitasController::class, 'destroy']);

    // API CMS Promo (admin)
    Route::get('/admin/cms/promos', [CmsPromoController::class, 'index']);
    Route::post('/admin/cms/promos', [CmsPromoController::class, 'store']);
    Route::put('/admin/cms/promos/{id}', [CmsPromoController::class, 'updateById']);
    Route::delete('/admin/cms/promos/{id}', [CmsPromoController::class, 'destroyById']);
    Route::get('/admin/cms/promo', [CmsPromoController::class, 'show']);
    Route::match(['put', 'post'], '/admin/cms/promo', [CmsPromoController::class, 'update']);
    Route::delete('/admin/cms/promo/gambar', [CmsPromoController::class, 'destroyImage']);

    // API Gambar Panduan Ukuran (kelola: admin)
    Route::get('/admin/gambar-ukuran', [GambarUkuranController::class, 'adminIndex']);
    Route::post('/admin/gambar-ukuran', [GambarUkuranController::class, 'store']);
    Route::put('/admin/gambar-ukuran/{id}', [GambarUkuranController::class, 'update']);
    Route::delete('/admin/gambar-ukuran/{id}', [GambarUkuranController::class, 'destroy']);

    // Endpoint hapus gambar voucher (didaftarkan sebelum wildcard /vouchers/{id})
    Route::delete('/vouchers/{id}/gambar', [VoucherController::class, 'destroyImage']);

    // API Ringkasan (admin) - sebelum /admin/orders/{id}
    Route::get('/admin/orders/summary', [OrderController::class, 'adminSummary']);
    Route::get('/admin/payments/summary', [PaymentController::class, 'adminSummary']);

    // API Manajemen Pengguna (admin)
    Route::get('/admin/users/summary', [UserController::class, 'summary']);
    Route::get('/admin/users/export', [UserController::class, 'export']);

    // API Kategori (admin)
    Route::post('/admin/categories', [CategoryController::class, 'store']);
    Route::put('/admin/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);

    // API Pesanan & Pembayaran (admin)
    Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
    Route::get('/admin/orders/{id}', [OrderController::class, 'adminShow']);
    Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/admin/orders/{id}/fulfillments', [FulfillmentController::class, 'store']);
    Route::get('/admin/payments', [PaymentController::class, 'adminIndex']);

    // API Pemenuhan pesanan (admin)
    Route::get('/admin/order-fulfillments', [FulfillmentController::class, 'index']);

    // API Pengiriman (admin)
    Route::get('/admin/shipments', [ShipmentController::class, 'index']);
    Route::put('/admin/shipments/{id}/status', [ShipmentController::class, 'updateStatus']);

    // API Inventaris (admin)
    // Route statis wajib didaftarkan sebelum /admin/inventory/{variant_id}
    // agar tidak tertangkap wildcard parameternya.
    Route::get('/admin/inventory', [AdminInventoryController::class, 'index']);
    Route::get('/admin/inventory/movements', [AdminInventoryController::class, 'movements']);
    Route::post('/admin/inventory/adjustments', [AdminInventoryController::class, 'adjust']);
    Route::get('/admin/inventory/low-stock', [AdminInventoryController::class, 'lowStock']);
    Route::get('/admin/inventory/summary', [AdminInventoryController::class, 'summary']);
    Route::get('/admin/inventory/valuation', [AdminInventoryController::class, 'valuation']);
    Route::get('/admin/inventory/export', [AdminInventoryController::class, 'export']);
    Route::get('/admin/inventory/{variant_id}', [AdminInventoryController::class, 'show']);

    // API Stock Opname (admin)
    Route::get('/admin/stock-opnames', [AdminStockOpnameController::class, 'index']);
    Route::post('/admin/stock-opnames', [AdminStockOpnameController::class, 'store']);
    Route::get('/admin/stock-opnames/{id}', [AdminStockOpnameController::class, 'show']);
    Route::post('/admin/stock-opnames/{id}/items', [AdminStockOpnameController::class, 'storeItem']);
    Route::post('/admin/stock-opnames/{id}/complete', [AdminStockOpnameController::class, 'complete']);

    // API Dashboard admin
    Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary']);
    Route::get('/admin/dashboard/revenue', [AdminDashboardController::class, 'revenue']);
    Route::get('/admin/dashboard/orders', [AdminDashboardController::class, 'orders']);
    Route::get('/admin/dashboard/aov', [AdminDashboardController::class, 'aov']);

    // API Laporan keuangan (admin)
    Route::get('/admin/reports/summary', [AdminReportController::class, 'summary']);
    Route::get('/admin/reports/transactions', [AdminReportController::class, 'transactions']);
    Route::get('/admin/reports/export/pdf', [AdminReportController::class, 'exportPdf']);
    Route::get('/admin/reports/transactions/{id}', [AdminReportController::class, 'transactionDetail']);
});

// API monitoring & maintenance (khusus role teknisi).
Route::middleware(['auth:sanctum', 'teknisi'])
    ->prefix('teknisi')
    ->group(function () {
        Route::get('/dashboard', [TeknisiController::class, 'dashboard']);

        Route::get('/audit-logs', [TeknisiController::class, 'auditLogs']);
        Route::get('/audit-logs/{id}', [TeknisiController::class, 'auditLogDetail']);

        Route::get('/webhook-logs', [TeknisiController::class, 'webhookLogs']);
        Route::get('/webhook-logs/{id}', [TeknisiController::class, 'webhookLogDetail']);

        Route::get('/notification-logs', [TeknisiController::class, 'notificationLogs']);
        Route::get('/notification-logs/{id}', [TeknisiController::class, 'notificationLogDetail']);

        Route::get('/health', [TeknisiController::class, 'health']);
        Route::get('/failed-jobs', [TeknisiController::class, 'failedJobs']);
    });

// Webhook notifikasi Midtrans (publik, diverifikasi lewat signature + dibatasi per IP)
Route::post('/payments/midtrans/notification', [MidtransNotificationController::class, 'handle'])
    ->middleware('throttle:midtrans-webhook');

// Alias URL webhook yang sama; dipakai bila dashboard Midtrans diarahkan ke
// /api/midtrans/notification. Keduanya menuju handler identik.
Route::post('/midtrans/notification', [MidtransNotificationController::class, 'handle'])
    ->middleware('throttle:midtrans-webhook');

Route::get('/test-json', function () {
    return 'TEST LARAVEL BERHASIL';
});

// OTP Registrasi & Lupa Password
use App\Http\Controllers\Api\Public\OtpController;

Route::post('/otp/verify-registration', [OtpController::class, 'verifyRegistration'])
    ->middleware('throttle:otp-verify');
Route::post('/otp/resend-registration', [OtpController::class, 'resendRegistration'])
    ->middleware('throttle:otp-send');
Route::post('/forgot-password', [OtpController::class, 'forgotPassword'])
    ->middleware('throttle:otp-send');
Route::post('/forgot-password/verify-otp', [OtpController::class, 'forgotPasswordVerifyOtp'])
    ->middleware('throttle:otp-verify');
Route::post('/forgot-password/reset', [OtpController::class, 'forgotPasswordReset'])
    ->middleware('throttle:otp-verify');
Route::post('/forgot-password/resend-otp', [OtpController::class, 'forgotPasswordResendOtp'])
    ->middleware('throttle:otp-send');
