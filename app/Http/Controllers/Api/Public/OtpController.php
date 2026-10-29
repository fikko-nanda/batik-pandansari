<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    public function resendRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengirim ulang kode verifikasi untuk email ini.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Email sudah terverifikasi, tidak perlu mengirim ulang kode OTP.',
            ]);
        }

        $cooldown = $this->otpService->canResendRegistration($email);
        if (! $cooldown['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon tunggu sebelum mengirim ulang kode OTP.',
                'retry_after' => $cooldown['retry_after'],
            ], 429);
        }

        if (! $this->otpService->sendRegistrationOtp($user, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email OTP. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP verifikasi telah dikirim ulang ke email kamu.',
            'data' => [
                'expires_in_minutes' => 5,
            ],
        ]);
    }

    public function verifyRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'kode_otp' => ['sometimes', 'string', 'size:6'],
            'otp' => ['sometimes', 'string', 'size:6'],
        ]);

        if (empty($validated['kode_otp'] ?? null) && empty($validated['otp'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP wajib diisi.',
            ], 422);
        }

        $email = $validated['email'];
        $otp = $validated['kode_otp'] ?? ($validated['otp'] ?? '');

        $result = $this->otpService->verifyRegistrationOtp($email, $otp);
        if (! $result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if ($user
            && array_key_exists('email_verified_at', $user->getAttributes())
            && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi email berhasil.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            $cooldown = $this->otpService->canResendPasswordReset($email);
            if (! $cooldown['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mohon tunggu sebelum mengirim ulang kode OTP pemulihan.',
                    'retry_after' => $cooldown['retry_after'],
                ], 429);
            }

            if (! $this->otpService->sendPasswordResetOtp($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim email OTP pemulihan.',
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Jika email terdaftar, kami telah mengirim kode OTP pemulihan password ke email tersebut.',
        ]);
    }

    public function forgotPasswordResendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'success' => true,
                'message' => 'Jika email terdaftar, kami telah mengirim kode OTP pemulihan password ke email tersebut.',
            ]);
        }

        $cooldown = $this->otpService->canResendPasswordReset($email);
        if (! $cooldown['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon tunggu sebelum mengirim ulang kode OTP pemulihan.',
                'retry_after' => $cooldown['retry_after'],
            ], 429);
        }

        if (! $this->otpService->sendPasswordResetOtp($user, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email OTP pemulihan.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP pemulihan password telah dikirim ulang ke email kamu.',
            'data' => [
                'expires_in_minutes' => 5,
            ],
        ]);
    }

    public function forgotPasswordVerifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'kode_otp' => ['sometimes', 'string', 'size:6'],
            'otp' => ['sometimes', 'string', 'size:6'],
        ]);

        if (empty($validated['kode_otp'] ?? null) && empty($validated['otp'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP wajib diisi.',
            ], 422);
        }

        $email = $validated['email'];
        $otp = $validated['kode_otp'] ?? ($validated['otp'] ?? '');

        $result = $this->otpService->verifyPasswordResetOtp($email, $otp);
        if (! $result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP berhasil diverifikasi.',
            'data' => [
                'reset_token' => $result['reset_token'],
            ],
        ]);
    }

    public function forgotPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'reset_token' => ['sometimes', 'string'],
            'token' => ['sometimes', 'string'],
            'kata_sandi' => ['sometimes', 'string', new StrongPassword, 'confirmed'],
            'password' => ['sometimes', 'string', new StrongPassword, 'confirmed'],
        ]);

        if (empty($validated['reset_token'] ?? null) && empty($validated['token'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Token reset wajib diisi.',
            ], 422);
        }
        if (empty($validated['kata_sandi'] ?? null) && empty($validated['password'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi wajib diisi.',
            ], 422);
        }

        $email = $validated['email'];
        $resetToken = $validated['reset_token'] ?? ($validated['token'] ?? '');
        $password = $validated['kata_sandi'] ?? ($validated['password'] ?? '');

        $tokenRecord = $this->otpService->verifyResetToken($email, $resetToken);
        if (! $tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Token reset tidak valid atau sudah digunakan.',
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        DB::transaction(function () use ($user, $password) {
            $user->kata_sandi = $password;
            $user->save();
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset.',
        ]);
    }
}
