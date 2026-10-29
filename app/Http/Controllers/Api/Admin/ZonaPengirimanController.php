<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZonaPengiriman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ZonaPengirimanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_aktif' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $zones = ZonaPengiriman::query()
            ->when(
                array_key_exists('status_aktif', $validated),
                fn ($query) => $query->where('status_aktif', (bool) $validated['status_aktif'])
            )
            ->when(
                ! empty($validated['search']),
                fn ($query) => $query->where('nama_zona', 'like', '%'.$validated['search'].'%')
            )
            ->orderBy('jarak_min_km')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar zona pengiriman berhasil diambil.',
            'data' => $zones,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $zona = ZonaPengiriman::find($id);

        if (! $zona) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail zona pengiriman berhasil diambil.',
            'data' => $zona,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_zona' => ['required', 'string', 'max:100', 'unique:zona_pengiriman,nama_zona'],
            'jarak_min_km' => ['required', 'numeric', 'min:0', 'max:999999'],
            'jarak_maks_km' => ['required', 'numeric', 'gt:jarak_min_km', 'max:999999'],
            'biaya_dasar' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'biaya_per_kg' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        $statusAktif = (bool) ($validated['status_aktif'] ?? true);

        $this->assertNoOverlap(
            (float) $validated['jarak_min_km'],
            (float) $validated['jarak_maks_km'],
            $statusAktif
        );

        $zona = ZonaPengiriman::create([
            'id_zona' => (string) Str::uuid(),
            'nama_zona' => $validated['nama_zona'],
            'jarak_min_km' => $validated['jarak_min_km'],
            'jarak_maks_km' => $validated['jarak_maks_km'],
            'biaya_dasar' => $validated['biaya_dasar'] ?? 0,
            'biaya_per_kg' => $validated['biaya_per_kg'] ?? 0,
            'status_aktif' => $statusAktif,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zona pengiriman berhasil dibuat.',
            'data' => $zona,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $zona = ZonaPengiriman::find($id);

        if (! $zona) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'nama_zona' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('zona_pengiriman', 'nama_zona')->ignore($zona->id_zona, 'id_zona'),
            ],
            'jarak_min_km' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999'],
            'jarak_maks_km' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999'],
            'biaya_dasar' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999'],
            'biaya_per_kg' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);

        $jarakMin = (float) ($validated['jarak_min_km'] ?? $zona->jarak_min_km);
        $jarakMaks = (float) ($validated['jarak_maks_km'] ?? $zona->jarak_maks_km);
        $statusAktif = (bool) ($validated['status_aktif'] ?? $zona->status_aktif);

        if ($jarakMaks <= $jarakMin) {
            throw ValidationException::withMessages([
                'jarak_maks_km' => ['Jarak maksimum harus lebih besar dari jarak minimum.'],
            ]);
        }

        $this->assertNoOverlap($jarakMin, $jarakMaks, $statusAktif, $zona->id_zona);

        $zona->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Zona pengiriman berhasil diperbarui.',
            'data' => $zona,
        ]);
    }

    private function assertNoOverlap(
        float $jarakMin,
        float $jarakMaks,
        bool $statusAktif,
        ?string $exceptId = null
    ): void {
        if (! $statusAktif) {
            return;
        }

        $overlap = ZonaPengiriman::query()
            ->where('status_aktif', true)
            ->when($exceptId, fn ($query) => $query->where('id_zona', '!=', $exceptId))
            ->where('jarak_min_km', '<', $jarakMaks)
            ->where('jarak_maks_km', '>', $jarakMin)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'jarak_min_km' => ['Rentang jarak tumpang tindih dengan zona aktif lain.'],
            ]);
        }
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Zona pengiriman tidak ditemukan.',
        ], 404);
    }
}
