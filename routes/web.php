<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Halaman hasil pembayaran
|--------------------------------------------------------------------------
|
| Titik akhir pengalihan (finish/unfinish/error) Midtrans Snap. Halaman ini
| SENGAJA bersifat informatif dan TIDAK menjadikan parameter URL (order_id,
| transaction_status, status_code, dsb.) sebagai bukti pembayaran — parameter
| tersebut mudah dipalsukan. Status resmi hanya ditetapkan lewat webhook
| POST /api/payments/midtrans/notification yang tervalidasi signature, dan
| dapat diperiksa lewat GET /api/orders/{id}/payment.
|
*/
Route::view('/pembayaran/selesai', 'pembayaran.hasil', ['keadaan' => 'selesai'])
    ->name('pembayaran.selesai');

Route::view('/pembayaran/tertunda', 'pembayaran.hasil', ['keadaan' => 'tertunda'])
    ->name('pembayaran.tertunda');

Route::view('/pembayaran/gagal', 'pembayaran.hasil', ['keadaan' => 'gagal'])
    ->name('pembayaran.gagal');
