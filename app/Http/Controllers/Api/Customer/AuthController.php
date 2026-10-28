<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request, OtpService $otpService): JsonResponse
    {
        $validated = $request->validate([
            'nama_pengguna' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'kata_sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => $validated['nama_pengguna'],
            'email' => $validated['email'],
            'nomor_telepon' => $validated['nomor_telepon'] ?? null,
            'kata_sandi' => $validated['kata_sandi'],
            'peran' => 'pelanggan',
        ]);

        $otpSent = $otpService->sendRegistrationOtp($user);

        if (! $otpSent) {
            Log::warning('Gagal mengirim email OTP registrasi.', [
                'email' => $user->email,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $otpSent
                ? 'Registrasi berhasil. Kode verifikasi telah dikirim ke email kamu.'
                : 'Registrasi berhasil, namun gagal mengirim kode verifikasi. Gunakan fitur kirim ulang kode OTP.',
            'data' => $user,
            'otp_sent' => $otpSent,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'kata_sandi' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Pengguna khusus Google memiliki kata_sandi NULL; Hash::check tidak
        // boleh dipanggil dengannya.
        if (! $user || ! $user->kata_sandi || ! Hash::check($validated['kata_sandi'], $user->kata_sandi)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diambil.',
            'data' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama_pengguna' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id_user, 'id_user'),
            ],
            'nomor_telepon' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kata_sandi_saat_ini' => ['required', 'string'],
            'kata_sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['kata_sandi_saat_ini'], $user->kata_sandi)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini salah.',
            ], 422);
        }

        $user->update(['kata_sandi' => $validated['kata_sandi']]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
            'data' => ['email' => $user->email],
        ]);
    }
}
