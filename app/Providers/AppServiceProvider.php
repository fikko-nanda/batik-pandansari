<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Worker queue dev harus mencoba ulang cukup kali: OTP/email bisa
        // sementara ditolak SMTP (mis. Mailtrap "Too many emails per second").
        // Nilai default framework (--tries=1) membuat email gagal permanen.
        DevCommands::artisan('queue:listen --tries=10 --timeout=60 --sleep=1', 'queue');
        // Endpoint webhook Midtrans bersifat publik (dipanggil server Midtrans),
        // jadi dibatasi per IP agar tidak bisa dibanjiri pihak tak bertanggung jawab.
        RateLimiter::for('midtrans-webhook', fn (Request $request) => Limit::perMinute(
            (int) config('services.midtrans.webhook_throttle', 60)
        )->by($request->ip()));

        // Endpoint OTP bersifat publik dan memicu pengiriman email,
        // jadi dibatasi per email+IP (anti brute-force & pembajakan kotak masuk)
        // sekaligus per IP (anti banjir dari satu sumber).
        RateLimiter::for('otp-send', function (Request $request) {
            $key = $request->ip().'|'.mb_strtolower((string) $request->input('email', ''));

            return [
                Limit::perMinute(5)->by('otp-send|'.$key),
                Limit::perMinute(30)->by('otp-send-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $key = $request->ip().'|'.mb_strtolower((string) $request->input('email', ''));

            return [
                Limit::perMinute(10)->by('otp-verify|'.$key),
                Limit::perMinute(60)->by('otp-verify-ip|'.$request->ip()),
            ];
        });

        // Endpoint login/tautan Google memicu verifikasi ID Token terhadap
        // JWKS Google, jadi dibatasi per IP agar tidak dipakai membanjiri
        // layanan Google dari satu sumber.
        RateLimiter::for('google-auth', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // Batas pengiriman email keluar dari worker queue. Mailtrap (dan
        // sebagian penyedia SMTP) membatasi jumlah email per detik; batas ini
        // mencegah job gagal 550 "Too many emails per second". Default 1
        // email per 2 detik (aman untuk plan testing Mailtrap).
        RateLimiter::for('email', function () {
            $seconds = max(1, (int) config('mail.outbound_rate_seconds', 2));

            return (new Limit(1, $seconds))->by('email');
        });

        // Cek ongkir serentak (JNE/J&T/POS) menyentuh API RajaOngkir yang
        // berkuota, jadi dibatasi per pengguna (bukan per IP agar pelanggan
        // di jaringan kantor/warnet yang sama tidak saling memblokir).
        RateLimiter::for('rajaongkir-check', function (Request $request) {
            $userId = (string) optional($request->user())->getAuthIdentifier();

            return [
                Limit::perMinute(20)->by('rajaongkir-check|'.($userId !== '' ? $userId : $request->ip())),
                Limit::perMinute(60)->by('rajaongkir-check-ip|'.$request->ip()),
            ];
        });
    }
}
