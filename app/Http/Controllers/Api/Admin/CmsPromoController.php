<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPromo;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * CMS Kelola Promo (Admin).
 *
 * GET/POST/PUT/DELETE /api/admin/cms/promos kelola beberapa banner promo
 * GET    /api/admin/cms/promo         konfigurasi banner promo + voucher terpilih
 * PUT    /api/admin/cms/promo         upsert banner promo + upload gambar baru
 * DELETE /api/admin/cms/promo/gambar  hapus gambar banner promo (idempoten)
 */
class CmsPromoController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $promos = CmsPromo::with(['voucher.productPeruntukan'])
            ->orderBy('dibuat_pada', 'desc')
            ->orderBy('id_cms_promo', 'desc')
            ->get()
            ->map(fn (CmsPromo $promo) => $this->presentPromo($promo));

        return response()->json([
            'success' => true,
            'message' => 'Daftar konfigurasi promo berhasil diambil.',
            'data' => $promos,
        ]);
    }

    public function show(): JsonResponse
    {
        $promo = CmsPromo::with(['voucher.productPeruntukan'])
            ->find(CmsPromo::SINGLETON_ID);

        return response()->json([
            'success' => true,
            'message' => $promo ? 'Konfigurasi promo berhasil diambil.' : 'Konfigurasi promo belum diatur.',
            'data' => $promo ? $this->presentPromo($promo) : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_voucher' => ['required', 'string', 'exists:vouchers,id_voucher'],
            'badge' => ['nullable', 'string', 'max:40'],
            'tautan_tujuan' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && $value !== '' && ! str_starts_with((string) $value, '/') && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('Tautan tujuan harus berupa path relatif (diawali /) atau URL absolut.');
                    }
                },
            ],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'id_voucher.required' => 'Voucher wajib dipilih.',
            'id_voucher.exists' => 'Voucher tidak ditemukan.',
            'badge.max' => 'Badge tidak boleh lebih dari 40 karakter.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $promo = CmsPromo::find(CmsPromo::SINGLETON_ID);

        if (! $promo) {
            $promo = new CmsPromo;
            $promo->id_cms_promo = CmsPromo::SINGLETON_ID;
        }

        if ($request->hasFile('gambar')) {
            $promo->gambar = $this->images->replace(
                $request->file('gambar'),
                'cms/promo',
                $promo->exists ? $promo->gambar : null
            );
        }

        $promo->id_voucher = $validated['id_voucher'];
        $promo->badge = $validated['badge'] ?? null;
        $promo->tautan_tujuan = $validated['tautan_tujuan'] ?? null;
        $promo->save();
        $promo->load(['voucher.productPeruntukan']);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi promo berhasil disimpan.',
            'data' => $this->presentPromo($promo),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_voucher' => ['required', 'string', 'exists:vouchers,id_voucher'],
            'badge' => ['nullable', 'string', 'max:40'],
            'tautan_tujuan' => ['nullable', 'string', 'max:500'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'urutan_tampilan' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $promo = new CmsPromo(Arr::only($validated, [
            'id_voucher',
            'badge',
            'tautan_tujuan',
            'urutan_tampilan',
        ]));

        if ($request->hasFile('gambar')) {
            $promo->gambar = $this->images->store($request->file('gambar'), 'cms/promo');
        }

        $promo->save();
        $promo->load(['voucher.productPeruntukan']);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi promo berhasil dibuat.',
            'data' => $this->presentPromo($promo),
        ], 201);
    }

    public function updateById(Request $request, string $id): JsonResponse
    {
        $promo = CmsPromo::findOrFail($id);

        $validated = $request->validate([
            'id_voucher' => ['sometimes', 'required', 'string', 'exists:vouchers,id_voucher'],
            'badge' => ['sometimes', 'nullable', 'string', 'max:40'],
            'tautan_tujuan' => ['sometimes', 'nullable', 'string', 'max:500'],
            'gambar' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'urutan_tampilan' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
        ]);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $this->images->replace(
                $request->file('gambar'),
                'cms/promo',
                $promo->gambar
            );
        }

        $promo->fill(Arr::only($validated, [
            'id_voucher',
            'badge',
            'tautan_tujuan',
            'gambar',
            'urutan_tampilan',
        ]));
        $promo->save();
        $promo->load(['voucher.productPeruntukan']);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi promo berhasil diperbarui.',
            'data' => $this->presentPromo($promo),
        ]);
    }

    public function destroyById(string $id): JsonResponse
    {
        $promo = CmsPromo::findOrFail($id);
        $this->images->delete($promo->gambar);
        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi promo berhasil dihapus.',
        ]);
    }

    public function destroyImage(): JsonResponse
    {
        $promo = CmsPromo::find(CmsPromo::SINGLETON_ID);

        if ($promo && $promo->gambar) {
            $this->images->delete($promo->gambar);
            $promo->update(['gambar' => null]);
        }

        $data = null;
        if ($promo) {
            $promo->load(['voucher.productPeruntukan']);
            $data = $this->presentPromo($promo);
        }

        return response()->json([
            'success' => true,
            'message' => 'Gambar banner promo berhasil dihapus.',
            'data' => $data,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPromo(CmsPromo $promo): array
    {
        $voucherData = null;
        if ($promo->voucher) {
            $voucher = $promo->voucher;
            $voucherData = array_merge($voucher->toArray(), [
                'url_gambar' => $this->images->url($voucher->gambar),
            ]);
        }

        return [
            'id_cms_promo' => $promo->id_cms_promo,
            'id_voucher' => $promo->id_voucher,
            'badge' => $promo->badge,
            'tautan_tujuan' => $promo->tautan_tujuan,
            'gambar' => $promo->gambar,
            'url_gambar' => $this->images->url($promo->gambar),
            'urutan_tampilan' => $promo->urutan_tampilan,
            'voucher' => $voucherData,
            'dibuat_pada' => $promo->dibuat_pada,
            'diperbarui_pada' => $promo->diperbarui_pada,
        ];
    }
}
