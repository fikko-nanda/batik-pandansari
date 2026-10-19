<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\MidtransNotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\VoucherUsageController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth:sanctum', 'admin']);

// API Autentikasi
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

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
    Route::get('/vouchers/available', [VoucherController::class, 'available']);
    Route::post('/vouchers/validate', [VoucherController::class, 'validateVoucher']);
    Route::get('/voucher-usages/me', [VoucherUsageController::class, 'mine']);

    // API Checkout & Pesanan pelanggan (login, hanya milik sendiri)
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // API Pembayaran pelanggan (login, hanya milik sendiri)
    Route::post('/orders/{id}/payment', [PaymentController::class, 'store']);
    Route::get('/orders/{id}/payment', [PaymentController::class, 'show']);
});

// API Kategori
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// API Produk (baca: publik)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// API Varian & Gambar Produk (baca: publik)
Route::get('/products/{id}/variants', [ProductVariantController::class, 'index']);
Route::get('/products/{id}/images', [ProductImageController::class, 'index']);

// API Produk (tulis: admin)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/products', [ProductController::class, 'adminIndex']);
    Route::get('/admin/products/{id}', [ProductController::class, 'adminShow']);

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
    Route::get('/vouchers/{id}', [VoucherController::class, 'show']);
    Route::put('/vouchers/{id}', [VoucherController::class, 'update']);
    Route::delete('/vouchers/{id}', [VoucherController::class, 'destroy']);
    Route::get('/voucher-usages', [VoucherUsageController::class, 'index']);

    // API Pesanan & Pembayaran (admin)
    Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
    Route::get('/admin/orders/{id}', [OrderController::class, 'adminShow']);
    Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::get('/admin/payments', [PaymentController::class, 'adminIndex']);
});

// Webhook notifikasi Midtrans (publik, diverifikasi lewat SDK Midtrans)
Route::post('/payments/midtrans/notification', [MidtransNotificationController::class, 'handle']);

Route::get('/test-json', function () {
    return 'TEST LARAVEL BERHASIL';
});
