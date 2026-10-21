<?php

namespace Tests\Feature\Endpoints;

use App\Mail\PasswordResetOtpMail;
use App\Mail\RegistrationOtpMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Endpoint: /api/register (OTP otomatis), /api/otp/*, /api/forgot-password*
 */
class OtpEndpointTest extends EndpointTestCase
{
    protected static string $group = 'OTP Registrasi & Lupa Password';

    private function latestOtp(string $mailable): string
    {
        $sent = Mail::queued($mailable);

        $this->assertNotEmpty($sent, "Email {$mailable} tidak terkirim.");

        $otp = (string) $sent->last()->otp;

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp, 'OTP harus 6 digit.');

        return $otp;
    }

    private function wrongOtp(string $otp): string
    {
        return $otp === '000000' ? '111111' : '000000';
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function registerPelanggan(string $email = 'otp.pelanggan@example.com'): array
    {
        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi pelanggan (memicu OTP otomatis)',
            'json' => [
                'nama_pengguna' => 'Pelanggan OTP',
                'email' => $email,
                'nomor_telepon' => '081234567890',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 201,
        ]);

        return [$this->recorded[array_key_last($this->recorded)], $email];
    }

    public function test_register_otomatis_kirim_otp_lalu_verifikasi_berhasil(): void
    {
        Mail::fake();

        [$entry, $email] = $this->registerPelanggan();

        $this->assertTrue($entry['response']['otp_sent'] ?? false, 'Registrasi harus melaporkan otp_sent.');
        Mail::assertQueued(
            RegistrationOtpMail::class,
            fn (RegistrationOtpMail $mail) => $mail->hasTo($email) && $mail->isResend === false
        );
        $this->note('Registrasi otomatis membuat & mengirim email OTP (tanpa perlu panggil endpoint resend).');

        $otp = $this->latestOtp(RegistrationOtpMail::class);

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'Verifikasi OTP registrasi dengan kode benar',
            'json' => ['email' => $email, 'kode_otp' => $otp],
            'expected' => 200,
        ]);

        $this->assertTrue($this->recorded[1]['response']['success'] ?? false);
        $this->assertNotNull(
            DB::table('users')->where('email', $email)->value('email_verified_at'),
            'users.email_verified_at harus terisi setelah verifikasi.'
        );
        $this->assertSame(1, DB::table('registration_otps')->where('email', $email)->where('used', true)->count());

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'Verifikasi ulang memakai OTP yang sudah terpakai',
            'json' => ['email' => $email, 'otp' => $otp],
            'expected' => 422,
        ]);

        $this->assertStringContainsString(
            'tidak valid',
            (string) ($this->recorded[2]['response']['message'] ?? '')
        );

        $this->assertRecorded();
    }

    public function test_otp_salah_dibatasi_lima_percobaan(): void
    {
        Mail::fake();

        [, $email] = $this->registerPelanggan('otp.salah@example.com');
        $otp = $this->latestOtp(RegistrationOtpMail::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->hit('POST', '/api/otp/verify-registration', [
                'endpoint' => 'POST /api/otp/verify-registration',
                'label' => "Verifikasi OTP salah ke-{$i}",
                'json' => ['email' => $email, 'otp' => $this->wrongOtp($otp)],
                'expected' => 422,
            ]);
        }

        $record = DB::table('registration_otps')->where('email', $email)->orderByDesc('id')->first();
        $this->assertSame(5, (int) $record->attempts);
        $this->assertSame(1, (int) $record->used);
        $this->note('Setelah 5 percobaan salah, OTP otomatis dinonaktifkan.');

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'Verifikasi dengan OTP benar setelah melewati batas percobaan',
            'json' => ['email' => $email, 'otp' => $otp],
            'expected' => 422,
        ]);

        $this->assertStringContainsString(
            'tidak valid',
            (string) ($this->recorded[6]['response']['message'] ?? '')
        );
        $this->assertNull(DB::table('users')->where('email', $email)->value('email_verified_at'));

        $this->assertRecorded();
    }

    public function test_resend_registrasi_terkena_cooldown_lalu_berhasil(): void
    {
        Mail::fake();

        [, $email] = $this->registerPelanggan('otp.resend@example.com');
        $otpLama = $this->latestOtp(RegistrationOtpMail::class);

        $this->hit('POST', '/api/otp/resend-registration', [
            'endpoint' => 'POST /api/otp/resend-registration',
            'label' => 'Kirim ulang OTP sebelum cooldown berakhir',
            'json' => ['email' => $email],
            'expected' => 429,
        ]);

        $this->assertArrayHasKey('retry_after', $this->recorded[1]['response']);
        Mail::assertQueuedTimes(RegistrationOtpMail::class, 1);
        $this->note('Cooldown pengiriman ulang OTP adalah 300 detik.');

        $this->travel(301)->seconds();

        $this->hit('POST', '/api/otp/resend-registration', [
            'endpoint' => 'POST /api/otp/resend-registration',
            'label' => 'Kirim ulang OTP setelah cooldown berakhir',
            'json' => ['email' => $email],
            'expected' => 200,
        ]);

        Mail::assertQueuedTimes(RegistrationOtpMail::class, 2);
        $this->assertTrue(Mail::queued(RegistrationOtpMail::class)->last()->isResend);
        $otpBaru = $this->latestOtp(RegistrationOtpMail::class);

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'OTP lama dipakai setelah kirim ulang (harus gagal)',
            'json' => ['email' => $email, 'otp' => $otpLama],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'OTP baru dipakai setelah kirim ulang (harus berhasil)',
            'json' => ['email' => $email, 'otp' => $otpBaru],
            'expected' => 200,
        ]);

        $this->assertNotNull(DB::table('users')->where('email', $email)->value('email_verified_at'));

        $this->assertRecorded();
    }

    public function test_lupa_password_sampai_reset_dan_login_ulang(): void
    {
        Mail::fake();

        $user = $this->makeUser('pelanggan', 'password123');

        $this->hit('POST', '/api/forgot-password', [
            'endpoint' => 'POST /api/forgot-password',
            'label' => 'Minta kode OTP pemulihan password',
            'json' => ['email' => $user->email],
            'expected' => 200,
        ]);

        Mail::assertQueued(
            PasswordResetOtpMail::class,
            fn (PasswordResetOtpMail $mail) => $mail->hasTo($user->email)
        );
        $otp = $this->latestOtp(PasswordResetOtpMail::class);

        $verify = $this->hit('POST', '/api/forgot-password/verify-otp', [
            'endpoint' => 'POST /api/forgot-password/verify-otp',
            'label' => 'Verifikasi OTP pemulihan password',
            'json' => ['email' => $user->email, 'kode_otp' => $otp],
            'expected' => 200,
        ]);

        $resetToken = (string) $verify->json('data.reset_token');
        $this->assertNotSame('', $resetToken, 'reset_token harus diterbitkan setelah OTP valid.');

        // Token login lama harus ikut dicabut setelah password direset.
        $this->loginAs($user);
        $this->app->make('auth')->forgetGuards();

        $this->hit('POST', '/api/forgot-password/reset', [
            'endpoint' => 'POST /api/forgot-password/reset',
            'label' => 'Reset password dengan reset_token',
            'json' => [
                'email' => $user->email,
                'reset_token' => $resetToken,
                'kata_sandi' => 'sandiBaru123',
                'kata_sandi_confirmation' => 'sandiBaru123',
            ],
            'expected' => 200,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('sandiBaru123', $user->kata_sandi), 'Password baru harus tersimpan ter-hash.');
        $this->assertSame(0, $user->tokens()->count(), 'Semua token Sanctum harus dicabut setelah reset password.');
        $this->assertSame(1, (int) DB::table('password_reset_tokens_custom')->where('email', $user->email)->where('used', true)->count());

        $this->hit('POST', '/api/forgot-password/reset', [
            'endpoint' => 'POST /api/forgot-password/reset',
            'label' => 'Pakai ulang reset_token yang sudah terpakai',
            'json' => [
                'email' => $user->email,
                'reset_token' => $resetToken,
                'kata_sandi' => 'sandiLain123',
                'kata_sandi_confirmation' => 'sandiLain123',
            ],
            'expected' => 422,
        ]);

        $this->asGuest();
        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan password lama (harus gagal)',
            'json' => ['email' => $user->email, 'kata_sandi' => 'password123'],
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan password baru (harus berhasil)',
            'json' => ['email' => $user->email, 'kata_sandi' => 'sandiBaru123'],
            'expected' => 200,
        ]);

        $this->assertNotEmpty($this->recorded[array_key_last($this->recorded)]['response']['data']['token'] ?? null);

        $this->assertRecorded();
    }

    public function test_email_tidak_terdaftar_mendapat_respons_generik(): void
    {
        Mail::fake();

        $this->hit('POST', '/api/forgot-password', [
            'endpoint' => 'POST /api/forgot-password',
            'label' => 'Lupa password dengan email tidak terdaftar',
            'json' => ['email' => 'tidak-ada@example.com'],
            'expected' => 200,
        ]);

        $this->hit('POST', '/api/forgot-password/resend-otp', [
            'endpoint' => 'POST /api/forgot-password/resend-otp',
            'label' => 'Kirim ulang OTP pemulihan untuk email tidak terdaftar',
            'json' => ['email' => 'tidak-ada@example.com'],
            'expected' => 200,
        ]);

        $this->hit('POST', '/api/otp/resend-registration', [
            'endpoint' => 'POST /api/otp/resend-registration',
            'label' => 'Kirim ulang OTP registrasi untuk email tidak terdaftar',
            'json' => ['email' => 'tidak-ada@example.com'],
            'expected' => 404,
        ]);

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'Verifikasi OTP registrasi untuk email tidak terdaftar',
            'json' => ['email' => 'tidak-ada@example.com', 'kode_otp' => '123456'],
            'expected' => 422,
        ]);

        Mail::assertNothingQueued();
        $this->assertSame(0, DB::table('password_reset_otps')->count());
        $this->assertSame(0, DB::table('registration_otps')->count());
        $this->note('Email tidak terdaftar menghasilkan respons generik (anti-enumerasi) dan tidak mengirim email.');

        $this->assertRecorded();
    }

    public function test_rate_limit_verifikasi_otp(): void
    {
        Mail::fake();

        [, $email] = $this->registerPelanggan('otp.limit@example.com');
        $otp = $this->latestOtp(RegistrationOtpMail::class);

        $statuses = [];
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->hit('POST', '/api/otp/verify-registration', [
                'endpoint' => 'POST /api/otp/verify-registration',
                'label' => "Verifikasi OTP salah ke-{$i} (uji rate limit)",
                'json' => ['email' => $email, 'otp' => $this->wrongOtp($otp)],
                'expected' => 422,
            ]);
            $statuses[] = $response->getStatusCode();
        }

        $this->hit('POST', '/api/otp/verify-registration', [
            'endpoint' => 'POST /api/otp/verify-registration',
            'label' => 'Permintaan verifikasi ke-11 dalam satu menit (harus dibatasi)',
            'json' => ['email' => $email, 'otp' => $this->wrongOtp($otp)],
            'expected' => 429,
        ]);

        $this->assertSame(array_fill(0, 10, 422), $statuses);
        $this->note('Rate limit verifikasi OTP: 10 permintaan/menit per email+IP.');

        $this->assertRecorded();
    }

    public function test_rate_limit_pengiriman_otp(): void
    {
        Mail::fake();

        $user = $this->makeUser('pelanggan', 'password123');
        $email = $user->email;

        for ($i = 1; $i <= 5; $i++) {
            $this->hit('POST', '/api/forgot-password', [
                'endpoint' => 'POST /api/forgot-password',
                'label' => "Permintaan OTP ke-{$i} (uji rate limit)",
                'json' => ['email' => $email],
                'expected' => $i === 1 ? 200 : 429,
            ]);
        }

        $this->hit('POST', '/api/forgot-password', [
            'endpoint' => 'POST /api/forgot-password',
            'label' => 'Permintaan OTP ke-6 dalam satu menit (dibatasi rate limiter)',
            'json' => ['email' => $email],
            'expected' => 429,
        ]);

        $this->assertArrayNotHasKey(
            'retry_after',
            $this->recorded[5]['response'],
            'Respons ke-6 harus berasal dari rate limiter, bukan cooldown aplikasi.'
        );
        $this->note('Rate limit pengiriman OTP: 5 permintaan/menit per email+IP.');

        $this->assertRecorded();
    }

    public function test_otp_dikirim_lewat_queue_bukan_sinkron(): void
    {
        Mail::fake();

        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi memicu OTP tanpa mengirim SMTP sinkron di dalam request',
            'json' => [
                'nama_pengguna' => 'Pelanggan Async',
                'email' => 'async.register@example.com',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 201,
        ]);

        $this->assertTrue($this->recorded[0]['response']['otp_sent'] ?? false);
        Mail::assertQueued(RegistrationOtpMail::class);
        Mail::assertNothingSent();
        $this->note('OTP registrasi di-queue (ShouldQueue); request tidak menunggu SMTP sehingga server tidak terblokir.');

        $user = $this->makeUser('pelanggan', 'password123');
        $this->hit('POST', '/api/forgot-password', [
            'endpoint' => 'POST /api/forgot-password',
            'label' => 'Lupa password memicu OTP tanpa mengirim SMTP sinkron di dalam request',
            'json' => ['email' => $user->email],
            'expected' => 200,
        ]);

        Mail::assertQueued(PasswordResetOtpMail::class);
        Mail::assertNothingSent();
        $this->note('OTP pemulihan password juga di-queue, bukan dikirim sinkron.');

        $this->assertRecorded();
    }

    public function test_mailable_otp_menandai_should_queue(): void
    {
        $this->assertInstanceOf(
            ShouldQueue::class,
            new RegistrationOtpMail('123456', 'Uji'),
            'RegistrationOtpMail harus ShouldQueue agar tidak memblokir request.'
        );
        $this->assertInstanceOf(
            ShouldQueue::class,
            new PasswordResetOtpMail('123456', 'Uji'),
            'PasswordResetOtpMail harus ShouldQueue agar tidak memblokir request.'
        );

        $this->note('Kedua mailable OTP mengimplementasikan ShouldQueue (dikirim oleh worker, bukan di dalam request HTTP).');
    }
}
