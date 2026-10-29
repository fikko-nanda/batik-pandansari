<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfilUsaha;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CMS Profil Usaha (identitas toko: kontak, alamat, jam operasional).
 *
 * Tabel `profil_usaha` dirancang satu baris (id_profil_usaha = 1).
 * `jam_operasional` disimpan sebagai JSON (longtext) berisi daftar hari/jam.
 *
 * GET  /api/admin/cms/profil-usaha   profil (admin)
 * PUT  /api/admin/cms/profil-usaha   ubah parsial + ganti logo opsional (admin)
 */
class CmsProfilUsahaController extends Controller
{
    private const SINGLETON_ID = 1;

    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $profil = ProfilUsaha::find(self::SINGLETON_ID);

        return response()->json([
            'success' => true,
            'message' => $profil ? 'Profil usaha berhasil diambil.' : 'Profil usaha belum tersedia.',
            'data' => $profil ? $this->withUrl($profil) : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_usaha' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi_singkat' => ['sometimes', 'nullable', 'string', 'max:255'],
            'deskripsi_lengkap' => ['sometimes', 'nullable', 'string'],
            'logo' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email_resmi' => ['sometimes', 'nullable', 'email', 'max:255'],
            'nomor_whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'nomor_telepon' => ['sometimes', 'nullable', 'string', 'max:30'],
            'alamat_lengkap' => ['sometimes', 'nullable', 'string'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'tautan_google_maps' => ['sometimes', 'nullable', 'string', 'max:500'],
            'jam_operasional' => ['sometimes', 'nullable', 'array'],
            'jam_operasional.*.hari' => ['required_with:jam_operasional', 'string', 'max:30'],
            'jam_operasional.*.jam_buka' => ['nullable', 'string', 'max:20'],
            'jam_operasional.*.jam_tutup' => ['nullable', 'string', 'max:20'],
            'jam_operasional.*.tutup' => ['nullable', 'boolean'],
        ]);

        $profil = ProfilUsaha::find(self::SINGLETON_ID);

        if (! $profil) {
            if (empty($validated['nama_usaha'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama usaha wajib diisi saat pertama kali dibuat.',
                ], 422);
            }

            $profil = new ProfilUsaha;
            $profil->id_profil_usaha = self::SINGLETON_ID;
            $profil->nama_usaha = $validated['nama_usaha'];
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->images->replace(
                $request->file('logo'),
                'cms/profil',
                $profil->exists ? $profil->logo : null
            );
        }

        unset($validated['nama_usaha']);
        $profil->fill($validated)->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil usaha berhasil diperbarui.',
            'data' => $this->withUrl($profil->fresh()),
        ]);
    }

    private function withUrl(ProfilUsaha $profil): ProfilUsaha
    {
        $profil->setAttribute('url_logo', $this->images->url($profil->logo));

        return $profil;
    }
}
