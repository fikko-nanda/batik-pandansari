<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use App\Services\OsmGeocodingService;
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
        // Normalisasi alias field frontend (telepon, provinsi, kota, kecamatan, label, is_primary)
        if (! $request->filled('nomor_telepon') && $request->filled('telepon')) {
            $request->merge(['nomor_telepon' => $request->input('telepon')]);
        }
        if (! $request->filled('nomor_telepon') && $request->filled('phone')) {
            $request->merge(['nomor_telepon' => $request->input('phone')]);
        }
        if (! $request->filled('nama_provinsi') && $request->filled('provinsi')) {
            $request->merge(['nama_provinsi' => $request->input('provinsi')]);
        }
        if (! $request->filled('nama_kota') && $request->filled('kota')) {
            $request->merge(['nama_kota' => $request->input('kota')]);
        }
        if (! $request->filled('nama_kecamatan') && $request->filled('kecamatan')) {
            $request->merge(['nama_kecamatan' => $request->input('kecamatan')]);
        }
        if (! $request->filled('label_alamat') && $request->filled('label')) {
            $request->merge(['label_alamat' => $request->input('label')]);
        }
        if (! $request->has('alamat_utama') && $request->has('is_primary')) {
            $request->merge(['alamat_utama' => $request->boolean('is_primary')]);
        }

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
            'id_tujuan_rajaongkir' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();

        $this->resolveCoordinatesIfMissing($validated);

        $isFirstAddress = ! UserAddress::where('id_user', $user->id_user)->exists();
        $isPrimary = $validated['alamat_utama'] ?? false;
        $isPrimary = $isPrimary || $isFirstAddress;

        try {
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
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan alamat ke database. Silakan periksa kembali data alamat Anda.',
            ], 500);
        }
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

        // Normalisasi alias field frontend
        if (! $request->filled('nomor_telepon') && $request->filled('telepon')) {
            $request->merge(['nomor_telepon' => $request->input('telepon')]);
        }
        if (! $request->filled('nomor_telepon') && $request->filled('phone')) {
            $request->merge(['nomor_telepon' => $request->input('phone')]);
        }
        if (! $request->filled('nama_provinsi') && $request->filled('provinsi')) {
            $request->merge(['nama_provinsi' => $request->input('provinsi')]);
        }
        if (! $request->filled('nama_kota') && $request->filled('kota')) {
            $request->merge(['nama_kota' => $request->input('kota')]);
        }
        if (! $request->filled('nama_kecamatan') && $request->filled('kecamatan')) {
            $request->merge(['nama_kecamatan' => $request->input('kecamatan')]);
        }
        if (! $request->filled('label_alamat') && $request->filled('label')) {
            $request->merge(['label_alamat' => $request->input('label')]);
        }
        if (! $request->has('alamat_utama') && $request->has('is_primary')) {
            $request->merge(['alamat_utama' => $request->boolean('is_primary')]);
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
            'id_tujuan_rajaongkir' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $mergeData = array_merge($address->toArray(), $validated);
            $this->resolveCoordinatesIfMissing($mergeData);
            if (! empty($mergeData['latitude']) && ! empty($mergeData['longitude'])) {
                $validated['latitude'] = $mergeData['latitude'];
                $validated['longitude'] = $mergeData['longitude'];
            }
        }

        try {
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
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui alamat. Silakan periksa kembali data alamat Anda.',
            ], 500);
        }
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

    private function resolveCoordinatesIfMissing(array &$validated): void
    {
        if (! empty($validated['latitude']) && ! empty($validated['longitude'])) {
            return;
        }

        try {
            $queryWilayah = trim(implode(' ', array_filter([
                $validated['nama_kecamatan'] ?? '',
                $validated['nama_kota'] ?? '',
                $validated['nama_provinsi'] ?? '',
            ])));

            if (mb_strlen($queryWilayah) >= 3) {
                $geoResults = app(OsmGeocodingService::class)->search($queryWilayah);
                if (! empty($geoResults[0]['latitude']) && ! empty($geoResults[0]['longitude'])) {
                    $validated['latitude'] = $geoResults[0]['latitude'];
                    $validated['longitude'] = $geoResults[0]['longitude'];
                }
            }
        } catch (\Throwable) {
            // Abaikan kesalahan jika geocoding otomatis gagal
        }
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
