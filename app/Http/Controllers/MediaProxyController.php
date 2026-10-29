<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Throwable;

class MediaProxyController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __invoke(string $filename)
    {
        return $this->proxy('products', $filename);
    }

    public function cmsHero(string $filename)
    {
        return $this->proxy('cms/hero', $filename);
    }

    public function cmsKategori(string $filename)
    {
        return $this->proxy('cms/kategori', $filename);
    }

    public function cmsGaleri(string $filename)
    {
        return $this->proxy('cms/galeri', $filename);
    }

    public function cmsPromo(string $filename)
    {
        return $this->proxy('cms/promo', $filename);
    }

    public function cmsProfil(string $filename)
    {
        return $this->proxy('cms/profil', $filename);
    }

    public function vouchers(string $filename)
    {
        return $this->proxy('vouchers', $filename);
    }

    private function proxy(string $folder, string $filename)
    {
        $filename = basename($filename);

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            abort(404);
        }

        // 1. Cek langsung storage disk lokal
        $localPath = storage_path('app/public/'.$folder.'/'.$filename);
        if (file_exists($localPath)) {
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            return response()->file($localPath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400, immutable',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $apiUrl = (string) config('services.api.url', '');

        if ($apiUrl === '') {
            abort(404);
        }

        $origin = preg_replace('#/api/?$#i', '', $apiUrl);
        $origin = rtrim((string) $origin, '/');

        // Cegah deadlock: jangan lakukan HTTP request ke diri sendiri bila origin adalah localhost
        $originHost = parse_url($origin, PHP_URL_HOST);
        if (in_array($originHost, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            abort(404);
        }

        $url = $origin.'/storage/'.$folder.'/'.$filename;

        $caBundle = config('services.api.ca_bundle');

        try {
            $response = Http::withOptions([
                // Gunakan CA bundle kustom bila dikonfigurasi (mis. PHP Windows tanpa curl.cainfo).
                'verify' => $caBundle ?: true,
            ])->withHeaders([
                'ngrok-skip-browser-warning' => 'true',
                'Accept' => 'image/*',
            ])->connectTimeout(5)->timeout(10)->get($url);
        } catch (Throwable) {
            abort(502);
        }

        abort_unless($response->successful(), 404);

        $contentType = (string) $response->header('Content-Type');

        abort_unless(str_starts_with($contentType, 'image/'), 502);

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
