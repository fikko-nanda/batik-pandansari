<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = UserAddress::where('id_user', $request->user()->id_user)
            ->orderByDesc('alamat_utama')
            ->orderByDesc('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar alamat berhasil diambil.',
            'data' => $addresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label_alamat' => ['required', 'string', 'max:30'],
            'nama_penerima' => ['required', 'string', 'max:100'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'nama_provinsi' => ['required', 'string', 'max:60'],
            'nama_kota' => ['required', 'string', 'max:60'],
            'nama_kecamatan' => ['required', 'string', 'max:60'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'alamat_lengkap' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'alamat_utama' => ['nullable', 'boolean'],
            'id_tujuan_rajaongkir' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $isFirstAddress = ! UserAddress::where('id_user', $user->id_user)->exists();
        $isPrimary = $validated['alamat_utama'] ?? false;
        $isPrimary = $isPrimary || $isFirstAddress;

        $address = DB::transaction(function () use ($validated, $user, $isPrimary) {
            if ($isPrimary) {
                UserAddress::where('id_user', $user->id_user)
                    ->update(['alamat_utama' => false]);
            }

            return UserAddress::create([
                'id_user_address' => (string) Str::uuid(),
                'id_user' => $user->id_user,
                'label_alamat' => $validated['label_alamat'],
                'nama_penerima' => $validated['nama_penerima'],
                'nomor_telepon' => $validated['nomor_telepon'],
                'nama_provinsi' => $validated['nama_provinsi'],
                'nama_kota' => $validated['nama_kota'],
                'nama_kecamatan' => $validated['nama_kecamatan'],
                'kode_pos' => $validated['kode_pos'],
                'alamat_lengkap' => $validated['alamat_lengkap'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'alamat_utama' => $isPrimary,
                'id_tujuan_rajaongkir' => $validated['id_tujuan_rajaongkir'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil dibuat.',
            'data' => $address,
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail alamat berhasil diambil.',
            'data' => $address,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'label_alamat' => ['sometimes', 'required', 'string', 'max:30'],
            'nama_penerima' => ['sometimes', 'required', 'string', 'max:100'],
            'nomor_telepon' => ['sometimes', 'required', 'string', 'max:20'],
            'nama_provinsi' => ['sometimes', 'required', 'string', 'max:60'],
            'nama_kota' => ['sometimes', 'required', 'string', 'max:60'],
            'nama_kecamatan' => ['sometimes', 'required', 'string', 'max:60'],
            'kode_pos' => ['sometimes', 'required', 'string', 'max:10'],
            'alamat_lengkap' => ['sometimes', 'required', 'string'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'alamat_utama' => ['sometimes', 'boolean'],
            'id_tujuan_rajaongkir' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $address, $request) {
            if (($validated['alamat_utama'] ?? false) === true) {
                UserAddress::where('id_user', $request->user()->id_user)
                    ->where('id_user_address', '!=', $address->id_user_address)
                    ->update(['alamat_utama' => false]);
            }

            $address->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil diperbarui.',
            'data' => $address,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $address = $this->findOwnedAddress($request, $id);

        if (! $address) {
            return $this->notFound();
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil dihapus.',
        ]);
    }

    private function findOwnedAddress(Request $request, string $id): ?UserAddress
    {
        return UserAddress::where('id_user_address', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Alamat tidak ditemukan.',
        ], 404);
    }
}
