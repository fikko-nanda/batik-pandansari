<?php

use App\Http\Controllers\MediaProxyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - E-Commerce Batik Pandansari
|--------------------------------------------------------------------------
*/

// 1. Landing Page
Route::get('/', function () {
    return view('landing');
})->name('home');

// 2. Modul Shop / Toko (Public & Belanja)
Route::get('/katalog', function () {
    return view('shop.katalog');
})->name('katalog');

// 2b. Halaman Info (Public) — Ketentuan Layanan & Kebijakan Privasi
Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');

Route::get('/detail/{id?}', function ($id = 1) {
    return view('shop.product-detail', compact('id'));
})->name('product.detail');

Route::get('/media/products/{filename}', MediaProxyController::class)
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.products');

Route::get('/media/cms-hero/{filename}', [MediaProxyController::class, 'cmsHero'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.cms-hero');

Route::get('/media/cms-kategori/{filename}', [MediaProxyController::class, 'cmsKategori'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.cms-kategori');

Route::get('/media/cms-galeri/{filename}', [MediaProxyController::class, 'cmsGaleri'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.cms-galeri');

Route::get('/media/cms-promo/{filename}', [MediaProxyController::class, 'cmsPromo'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.cms-promo');

Route::get('/media/vouchers/{filename}', [MediaProxyController::class, 'vouchers'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.vouchers');

Route::get('/cart', function () {
    return view('shop.cart');
})->name('cart');

Route::get('/checkout', function () {
    return view('shop.checkout');
})->name('checkout');

// 3. Modul Customer / Akun Saya
Route::get('/profile', function () {
    return view('customer.profile');
})->name('customer.profile');

Route::get('/orders', function () {
    return view('customer.orders');
})->name('customer.orders');

Route::get('/address', function () {
    return view('customer.address');
})->name('customer.address');

Route::get('/wishlist', function () {
    return view('customer.wishlist');
})->name('customer.wishlist');

Route::get('/security', function () {
    return view('customer.security');
})->name('customer.security');

// Modul Autentikasi (Login & Register)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// 3b. Lupa Password (3 langkah: email → OTP → sandi baru)
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('forgot-password');

// 4. Modul Admin Dashboard
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/products', function () {
        return view('admin.products');
    })->name('admin.products');

    Route::get('/stock', function () {
        return view('admin.stock');
    })->name('admin.stock');

    Route::get('/orders', function () {
        return view('admin.orders');
    })->name('admin.orders');

    Route::get('/midtrans', function () {
        return view('admin.midtrans');
    })->name('admin.midtrans');

    Route::get('/sales-report', function () {
        return view('admin.sales-report');
    })->name('admin.sales-report');

    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('admin.settings');

    Route::get('/create-promo', function () {
        return view('admin.create-promo');
    })->name('admin.create-promo');

    Route::get('/userlist', function () {
        return view('admin.userlist');
    })->name('admin.userlist');
});

// 5. Modul Teknisi (Engineering Console) - DIPISAH AGAR URL STANDALONE
Route::prefix('teknisi')->group(function () {
    Route::get('/audit-logs', function () {
        return view('teknisi.audit-log');
    })->name('teknisi.audit-log');

    Route::get('/webhook-logs', function () {
        return view('teknisi.webhook-logs');
    })->name('teknisi.webhook-logs');
});
