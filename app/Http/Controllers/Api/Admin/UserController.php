<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Rules\UniqueWhatsAppNumber;
use App\Rules\WhatsAppNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manajemen pengguna (admin).
 *
 * GET  /api/users                 daftar pengguna (paginasi bawaan index)
 * POST /api/users                 tambah pengguna (peran: pelanggan|admin|teknisi)
 * GET  /api/users/{id}            detail pengguna (tanpa data sensitif)
 * GET  /api/admin/users/summary   ringkasan jumlah pengguna & per peran
 * GET  /api/admin/users/export    unduh daftar pengguna sebagai CSV
 */
class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderBy('nama_pengguna')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_pengguna' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'nomor_telepon' => ['nullable', 'string', new WhatsAppNumber, new UniqueWhatsAppNumber],
            'kata_sandi' => ['required', 'string', new StrongPassword, 'confirmed'],
            'peran' => ['nullable', 'string', 'in:pelanggan,admin,teknisi'],
        ]);

        try {
            $user = User::create([
                'id_user' => (string) Str::uuid(),
                'nama_pengguna' => $validated['nama_pengguna'],
                'email' => $validated['email'],
                'nomor_telepon' => $validated['nomor_telepon'] ?? null,
                'nomor_telepon_normalized' => User::normalizeWhatsAppNumber($validated['nomor_telepon'] ?? null),
                'kata_sandi' => $validated['kata_sandi'],
                'peran' => $validated['peran'] ?? 'pelanggan',
            ]);
        } catch (UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'nomor_telepon_normalized')) {
                throw ValidationException::withMessages([
                    'nomor_telepon' => ['Nomor WhatsApp sudah terdaftar. Gunakan nomor lain.'],
                ]);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => $user,
        ]);
    }

    public function summary(): JsonResponse
    {
        $perPeran = User::query()
            ->select('peran')
            ->selectRaw('count(*) as jumlah')
            ->groupBy('peran')
            ->pluck('jumlah', 'peran');

        $total = (int) $perPeran->sum();

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan pengguna berhasil diambil.',
            'data' => [
                'total_pengguna' => $total,
                'pengguna_aktif' => $total,
                'per_peran' => [
                    'pelanggan' => (int) ($perPeran['pelanggan'] ?? 0),
                    'admin' => (int) ($perPeran['admin'] ?? 0),
                    'teknisi' => (int) ($perPeran['teknisi'] ?? 0),
                ],
            ],
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'pengguna-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'id_user',
                'nama_pengguna',
                'email',
                'nomor_telepon',
                'peran',
                'dibuat_pada',
            ], ',', '"', '');

            User::query()->orderBy('nama_pengguna')->chunk(200, function ($users) use ($output) {
                foreach ($users as $user) {
                    fputcsv($output, [
                        $user->id_user,
                        $user->nama_pengguna,
                        $user->email,
                        $user->nomor_telepon,
                        $user->peran,
                        $user->dibuat_pada?->format('Y-m-d H:i:s'),
                    ], ',', '"', '');
                }
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
