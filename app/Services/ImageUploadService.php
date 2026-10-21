<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Penanganan unggahan gambar CMS.
 *
 * Memusatkan konvensi: berkas disimpan pada disk "public" di folder tertentu,
 * kolom disimpan sebagai path relatif, dan URL publik dihasilkan saat respons.
 * Mengganti gambar baru otomatis menghapus berkas lama agar tidak menumpuk.
 */
class ImageUploadService
{
    public const ALLOWED_MIMES = 'jpg,jpeg,png,webp';

    public const MAX_KILOBYTES = 2048;

    /**
     * Simpan berkas dan kembalikan path relatifnya.
     */
    public function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 'public');
    }

    /**
     * Ganti gambar lama dengan yang baru: berkas lama dihapus lebih dulu.
     */
    public function replace(UploadedFile $file, string $folder, ?string $oldPath): string
    {
        $path = $this->store($file, $folder);
        $this->delete($oldPath);

        return $path;
    }

    /**
     * Hapus berkas bila ada. Aman dipanggil dengan null atau path kosong.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * URL publik dari path relatif (null bila tidak ada gambar).
     */
    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
