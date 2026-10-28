<?php

namespace App\Services;

use App\Mail\PasswordResetOtpMail;
use App\Mail\RegistrationOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\PasswordResetTokenCustom;
use App\Models\RegistrationOtp;
use App\Models\User;
use App\Services\Logging\NotificationLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    public const OTP_TTL_MINUTES = 5;

    public const OTP_RESEND_COOLDOWN_SECONDS = 300;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private NotificationLogger $notificationLogger) {}

    public function generateOtp(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function createRegistrationOtp(string $email): RegistrationOtp
    {
        $this->invalidateRegistrationOtps($email);

        $otp = $this->generateOtp();
        $now = Carbon::now();

        return RegistrationOtp::create([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
            'sent_at' => $now,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'used' => false,
        ]);
    }

    public function createPasswordResetOtp(string $email): PasswordResetOtp
    {
        $this->invalidatePasswordResetOtps($email);

        $otp = $this->generateOtp();
        $now = Carbon::now();

        return PasswordResetOtp::create([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
            'sent_at' => $now,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'used' => false,
        ]);
    }

    /**
     * Buat OTP registrasi baru lalu kirim ke email pengguna.
     *
     * @return bool true bila email berhasil dikirim
     */
    public function sendRegistrationOtp(User $user, bool $isResend = false): bool
    {
        $this->invalidateRegistrationOtps($user->email);

        $otp = $this->generateOtp();
        $now = Carbon::now();

        RegistrationOtp::create([
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
            'sent_at' => $now,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'used' => false,
        ]);

        $log = $this->notificationLogger->queued(
            'registration_otp',
            $user->id_user,
            $user->email,
            'user',
            $user->id_user
        );

        $mailable = (new RegistrationOtpMail($otp, $user->nama_pengguna ?? '', $isResend))
            ->withNotificationLog($log);

        try {
            Mail::to($user->email)->queue($mailable);

            return true;
        } catch (\Throwable $e) {
            $this->notificationLogger->markFailed(
                $log?->id_notification_log,
                $e
            );
            report($e);

            return false;
        }
    }

    /**
     * Buat OTP pemulihan password baru lalu kirim ke email pengguna.
     *
     * @return bool true bila email berhasil dikirim
     */
    public function sendPasswordResetOtp(User $user, bool $isResend = false): bool
    {
        $this->invalidatePasswordResetOtps($user->email);

        $otp = $this->generateOtp();
        $now = Carbon::now();

        PasswordResetOtp::create([
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
            'sent_at' => $now,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'used' => false,
        ]);

        $log = $this->notificationLogger->queued(
            'password_reset_otp',
            $user->id_user,
            $user->email,
            'user',
            $user->id_user
        );

        $mailable = (new PasswordResetOtpMail($otp, $user->nama_pengguna ?? '', $isResend))
            ->withNotificationLog($log);

        try {
            Mail::to($user->email)->queue($mailable);

            return true;
        } catch (\Throwable $e) {
            $this->notificationLogger->markFailed(
                $log?->id_notification_log,
                $e
            );
            report($e);

            return false;
        }
    }

    public function invalidateRegistrationOtps(string $email): void
    {
        RegistrationOtp::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->update(['used' => true]);
    }

    public function invalidatePasswordResetOtps(string $email): void
    {
        PasswordResetOtp::where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->update(['used' => true]);
    }

    public function getLatestRegistrationOtp(string $email): ?RegistrationOtp
    {
        return RegistrationOtp::where('email', $email)
            ->orderByDesc('id')
            ->first();
    }

    public function getLatestPasswordResetOtp(string $email): ?PasswordResetOtp
    {
        return PasswordResetOtp::where('email', $email)
            ->orderByDesc('id')
            ->first();
    }

    public function canResendRegistration(string $email): array
    {
        $latest = $this->getLatestRegistrationOtp($email);
        if (! $latest || ! $latest->sent_at) {
            return ['allowed' => true, 'retry_after' => 0];
        }

        $elapsed = $latest->sent_at->diffInSeconds(Carbon::now());
        if ($elapsed < self::OTP_RESEND_COOLDOWN_SECONDS) {
            return ['allowed' => false, 'retry_after' => self::OTP_RESEND_COOLDOWN_SECONDS - $elapsed];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    public function canResendPasswordReset(string $email): array
    {
        $latest = $this->getLatestPasswordResetOtp($email);
        if (! $latest || ! $latest->sent_at) {
            return ['allowed' => true, 'retry_after' => 0];
        }

        $elapsed = $latest->sent_at->diffInSeconds(Carbon::now());
        if ($elapsed < self::OTP_RESEND_COOLDOWN_SECONDS) {
            return ['allowed' => false, 'retry_after' => self::OTP_RESEND_COOLDOWN_SECONDS - $elapsed];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    public function verifyRegistrationOtp(string $email, string $otp): array
    {
        $record = RegistrationOtp::where('email', $email)
            ->where('used', false)
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            return ['valid' => false, 'message' => 'Kode OTP tidak valid atau sudah digunakan.'];
        }

        if ($record->attempts >= $record->max_attempts) {
            $record->used = true;
            $record->save();

            return ['valid' => false, 'message' => 'Percobaan verifikasi telah melebihi batas.'];
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            $record->used = true;
            $record->save();

            return ['valid' => false, 'message' => 'Kode OTP telah kedaluwarsa.'];
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->attempts = ($record->attempts ?? 0) + 1;
            if ($record->attempts >= $record->max_attempts) {
                $record->used = true;
            }
            $record->save();

            return ['valid' => false, 'message' => 'Kode OTP salah.'];
        }

        $record->used = true;
        $record->save();

        return ['valid' => true];
    }

    public function verifyPasswordResetOtp(string $email, string $otp): array
    {
        $record = PasswordResetOtp::where('email', $email)
            ->where('used', false)
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            return ['valid' => false, 'message' => 'Kode OTP tidak valid atau sudah digunakan.'];
        }

        if ($record->attempts >= $record->max_attempts) {
            $record->used = true;
            $record->save();

            return ['valid' => false, 'message' => 'Percobaan verifikasi telah melebihi batas.'];
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            $record->used = true;
            $record->save();

            return ['valid' => false, 'message' => 'Kode OTP telah kedaluwarsa.'];
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->attempts = ($record->attempts ?? 0) + 1;
            if ($record->attempts >= $record->max_attempts) {
                $record->used = true;
            }
            $record->save();

            return ['valid' => false, 'message' => 'Kode OTP salah.'];
        }

        $record->used = true;
        $record->save();

        $token = Str::random(64);
        $now = Carbon::now();
        PasswordResetTokenCustom::create([
            'email' => $email,
            'token_hash' => Hash::make($token),
            'expires_at' => $now->copy()->addMinutes(self::OTP_TTL_MINUTES),
            'used' => false,
        ]);

        return ['valid' => true, 'reset_token' => $token];
    }

    public function verifyResetToken(string $email, string $token): ?PasswordResetTokenCustom
    {
        $record = PasswordResetTokenCustom::where('email', $email)
            ->where('used', false)
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            return null;
        }

        if (Carbon::now()->greaterThan($record->expires_at)) {
            $record->used = true;
            $record->save();

            return null;
        }

        if (! Hash::check($token, $record->token_hash)) {
            return null;
        }

        $record->used = true;
        $record->save();

        return $record;
    }
}
