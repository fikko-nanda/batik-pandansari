<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pengiriman (admin).
 *
 * GET  /api/admin/shipments              ?status=&kurir=&search=&dari=&sampai=
 * PUT  /api/admin/shipments/{id}/status  {status_pengiriman, nomor_resi?, lokasi?, keterangan?}
 *
 * Perubahan status dicatat ke shipment_histories dalam satu transaksi;
 * status dikirim menetapkan tanggal_dikirim, diterima menetapkan tanggal_diterima.
 */
class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(Shipment::STATUSES)],
            'kurir' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $shipments = Shipment::with(['order:id_order,nomor_pesanan,status_pesanan,nama_penerima'])
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pengiriman', $validated['status'])
            )
            ->when(
                ! empty($validated['kurir']),
                fn ($query) => $query->where('kurir', 'like', $validated['kurir'])
            )
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('nomor_resi', 'like', "%{$term}%")
                            ->orWhereHas('order', fn ($q) => $q->where('nomor_pesanan', 'like', "%{$term}%"));
                    });
                }
            )
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['sampai'])
            )
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengiriman berhasil diambil.',
            'data' => $shipments->items(),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
                'last_page' => $shipments->lastPage(),
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status_pengiriman' => ['required', 'string', Rule::in(Shipment::STATUSES)],
            'nomor_resi' => ['nullable', 'string', 'max:100'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $shipment = Shipment::find($id);

        if (! $shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Pengiriman tidak ditemukan.',
            ], 404);
        }

        if ($validated['status_pengiriman'] === $shipment->status_pengiriman) {
            return response()->json([
                'success' => false,
                'message' => 'Status pengiriman sama dengan status saat ini.',
            ], 422);
        }

        $nomorResi = array_key_exists('nomor_resi', $validated)
            ? $validated['nomor_resi']
            : $shipment->nomor_resi;

        if (in_array($validated['status_pengiriman'], ['dikirim', 'dalam_perjalanan'], true)
            && ($nomorResi === null || $nomorResi === '')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor resi wajib diisi sebelum paket dikirim.',
            ], 422);
        }

        $shipment = DB::transaction(function () use ($shipment, $validated, $nomorResi) {
            $statusSebelumnya = $shipment->status_pengiriman;

            $updates = [
                'status_pengiriman' => $validated['status_pengiriman'],
                'nomor_resi' => $nomorResi,
            ];

            if ($validated['status_pengiriman'] === 'dikirim' && $shipment->tanggal_dikirim === null) {
                $updates['tanggal_dikirim'] = now();
            }

            if ($validated['status_pengiriman'] === 'diterima' && $shipment->tanggal_diterima === null) {
                $updates['tanggal_diterima'] = now();
            }

            $shipment->update($updates);

            ShipmentHistory::create([
                'id_shipment_history' => (string) Str::uuid(),
                'id_shipment' => $shipment->id_shipment,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => $validated['status_pengiriman'],
                'lokasi' => $validated['lokasi'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'waktu_perubahan' => now(),
            ]);

            return $shipment->refresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Status pengiriman berhasil diperbarui.',
            'data' => $shipment->load(['histories', 'order:id_order,nomor_pesanan,status_pesanan']),
        ]);
    }
}
