<?php

namespace App\Providers;

use App\Services\Logging\NotificationLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Message;

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
        $this->registerMailTracking();

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

        // Pencarian alamat dan perhitungan ongkir memanggil layanan eksternal
        // (Nominatim/OSRM), jadi dibatasi per pengguna/IP.
        RateLimiter::for('shipping-search', fn (Request $request) => Limit::perMinute(
            (int) config('services.osm.search_per_minute', 20)
        )->by((string) ($request->user()?->id_user ?? $request->ip())));

        RateLimiter::for('shipping-quote', fn (Request $request) => Limit::perMinute(
            (int) config('services.osm.quote_per_minute', 30)
        )->by((string) ($request->user()?->id_user ?? $request->ip())));

        // Login dibatasi per email+IP (anti brute force satu akun) sekaligus
        // per IP (anti credential stuffing dari satu sumber).
        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return [
                Limit::perMinute((int) config('security.login_per_email_per_minute', 5))
                    ->by('login|'.$email.'|'.$request->ip()),
                Limit::perMinute((int) config('security.login_per_ip_per_minute', 30))
                    ->by('login-ip|'.$request->ip()),
            ];
        });

        // Registrasi memicu pengiriman email OTP, jadi dibatasi agar tidak
        // dipakai untuk spam email maupun membanjiri database.
        RateLimiter::for('register', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return [
                Limit::perMinute((int) config('security.register_per_email_per_minute', 5))
                    ->by('register|'.$email.'|'.$request->ip()),
                Limit::perMinute((int) config('security.register_per_ip_per_minute', 20))
                    ->by('register-ip|'.$request->ip()),
            ];
        });
    }

    /**
     * Hubungkan siklus kirim email Laravel ke notification_logs.
     *
     * - MessageSending menambah attempt_count saat transport mulai mengirim.
     * - MessageSent menandai "sent" (diterima transport), BUKAN "delivered".
     */
    private function registerMailTracking(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $id = $this->notificationLogIdFromHeaders($event->message);

            if ($id !== null) {
                app(NotificationLogger::class)->markAttempt($id);
            }
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $id = $this->notificationLogIdFromHeaders(
                $event->sent->getOriginalMessage()
            );

            if ($id !== null) {
                app(NotificationLogger::class)->markSent(
                    $id,
                    $event->sent->getMessageId()
                );
            }
        });
    }

    private function notificationLogIdFromHeaders(?object $message): ?int
    {
        if (! $message instanceof Message) {
            return null;
        }

        $header = $message->getHeaders()->get('X-Notification-Log-Id');

        if ($header === null) {
            return null;
        }

        $value = trim($header->getBodyAsString());

        return ctype_digit($value) ? (int) $value : null;
    }
}
