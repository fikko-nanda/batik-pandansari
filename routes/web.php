<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaProxyController;
use App\Http\Controllers\PanelSessionController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes - E-Commerce Batik Pandansari
|--------------------------------------------------------------------------
*/

// 1. Landing / Home Page
Route::get('/', [HomeController::class, 'index'])->name('home');

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

Route::get('/media/cms-profil/{filename}', [MediaProxyController::class, 'cmsProfil'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.cms-profil');

Route::get('/media/vouchers/{filename}', [MediaProxyController::class, 'vouchers'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('media.vouchers');

Route::get('/cart', function () {
    return view('shop.cart');
})->name('cart');

Route::get('/checkout', function () {
    return view('shop.checkout');
})->name('checkout');

/*
|--------------------------------------------------------------------------
| Halaman hasil pembayaran (Midtrans Snap)
|--------------------------------------------------------------------------
*/
Route::view('/pembayaran/selesai', 'pembayaran.hasil', ['keadaan' => 'selesai'])
    ->name('pembayaran.selesai');

Route::view('/pembayaran/tertunda', 'pembayaran.hasil', ['keadaan' => 'tertunda'])
    ->name('pembayaran.tertunda');

Route::view('/pembayaran/gagal', 'pembayaran.hasil', ['keadaan' => 'gagal'])
    ->name('pembayaran.gagal');

// 3. Modul Customer / Akun Saya
Route::get('/profile', function () {
    return view('customer.profile');
})->name('customer.profile');

Route::get('/orders', function () {
    return view('customer.orders');
})->name('customer.orders');

// Detail pesanan (customer) — sumber alur konfirmasi WhatsApp → bayar Midtrans Snap
Route::get('/orders/{id}', function ($id) {
    return view('orders.show', ['id' => $id]);
})->name('customer.order.show');

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

// Alur Redirect Langsung Google OAuth
Route::get('/auth/google', function () {
    $clientId = config('services.google.client_id');
    if (! $clientId) {
        return redirect('/login')->with('error', 'Google Client ID belum dikonfigurasi di server.');
    }

    $redirectUri = url('/auth/google/callback');
    $state = Str::random(40);
    session(['oauth_state' => $state]);

    $query = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'state' => $state,
        'prompt' => 'select_account',
    ]);

    return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
})->name('auth.google');

Route::get('/auth/google/callback', function (Request $request) {
    $code = $request->input('code');
    if (! $code) {
        return redirect('/login')->with('error', 'Login Google dibatalkan atau tidak mendapatkan izin.');
    }

    $clientId = config('services.google.client_id');
    $clientSecret = config('services.google.client_secret');
    $redirectUri = url('/auth/google/callback');

    try {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            return redirect('/login')->with('error', 'Gagal memvalidasi token dari Google.');
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;

        $userInfo = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo')
            ->json();

        if (! $userInfo || empty($userInfo['email'])) {
            return redirect('/login')->with('error', 'Gagal mengambil informasi profil dari akun Google.');
        }

        $googleId = (string) $userInfo['sub'];
        $email = (string) $userInfo['email'];

        $user = User::where('google_id', $googleId)->first();
        if ($user === null) {
            $user = User::where('email', $email)->first();
            if ($user === null) {
                $user = User::create([
                    'id_user' => (string) Str::uuid(),
                    'nama_pengguna' => $userInfo['name'] ?? explode('@', $email)[0],
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $userInfo['picture'] ?? null,
                    'peran' => 'pelanggan',
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->google_id = $googleId;
                if (! $user->avatar && ! empty($userInfo['picture'])) {
                    $user->avatar = $userInfo['picture'];
                }
                $user->email_verified_at = now();
                $user->save();
            }
        }

        Auth::login($user);
        $plainToken = $user->createToken('auth_token')->plainTextToken;

        return view('auth.google-callback', [
            'token' => $plainToken,
            'user' => $user,
        ]);
    } catch (Throwable $e) {
        Log::error('[GoogleOAuth] Callback error: '.$e->getMessage());

        return redirect('/login')->with('error', 'Terjadi kesalahan sistem saat memproses login Google.');
    }
})->name('auth.google.callback');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Sesi panel: token diverifikasi backend, lalu disimpan sebagai cookie HttpOnly.
Route::post('/session/panel', [PanelSessionController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('session.panel');

Route::post('/session/logout', [PanelSessionController::class, 'destroy'])
    ->middleware('throttle:30,1')
    ->name('session.logout');

// 3b. Lupa Password (3 langkah: email → OTP → sandi baru)
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

// 4. Modul Admin Dashboard (wajib cookie panel admin)
Route::prefix('admin')->middleware('panel:admin')->group(function () {
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

    Route::get('/shipping', function () {
        return view('admin.shipping');
    })->name('admin.shipping');

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
Route::prefix('teknisi')->middleware('panel:teknisi')->group(function () {
    Route::get('/', function () {
        return view('teknisi.index');
    })->name('teknisi.dashboard');

    Route::get('/audit-logs', function () {
        return view('teknisi.audit-log');
    })->name('teknisi.audit-log');

    Route::get('/webhook-logs', function () {
        return view('teknisi.webhook-logs');
    })->name('teknisi.webhook-logs');

    Route::get('/notification-logs', function () {
        return view('teknisi.notification-logs');
    })->name('teknisi.notification-logs');

    Route::get('/failed-jobs', function () {
        return view('teknisi.failed-jobs');
    })->name('teknisi.failed-jobs');
});
