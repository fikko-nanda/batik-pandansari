<?php

namespace App\Support;

use Illuminate\Foundation\Vite as BaseVite;

/**
 * Vite helper dengan fallback otomatis.
 *
 * Laravel menganggap dev server hidup hanya berdasarkan ada/tidaknya file
 * `public/hot`. Kalau Vite mati mendadak (crash / terminal ditutup) file itu
 * bisa tertinggal, sehingga CSS & JS diarahkan ke http://[::1]:5173 yang sudah
 * tidak ada -> halaman tampil polos tanpa styling.
 *
 * Class ini menambal itu: file hot hanya dipakai kalau dev server benar-benar
 * merespons. Bila tidak, asset otomatis diambil dari hasil `npm run build`
 * (public/build/manifest.json) sehingga tampilan tetap rapi.
 */
class Vite extends BaseVite
{
    /**
     * Cache hasil probe per URL dalam satu request.
     *
     * @var array<string, bool>
     */
    protected array $reachabilityCache = [];

    /**
     * Tentukan apakah HMR/dev server benar-benar berjalan.
     */
    public function isRunningHot()
    {
        if (! is_file($this->hotFile())) {
            return false;
        }

        $url = trim((string) @file_get_contents($this->hotFile()));

        if ($url === '') {
            return false;
        }

        return $this->reachabilityCache[$url] ??= $this->devServerResponds($url);
    }

    /**
     * Cek apakah dev server Vite merespons pada URL yang tercatat di file hot.
     */
    protected function devServerResponds(string $url): bool
    {
        $probeUrl = rtrim($url, '/').'/@vite/client';

        if (function_exists('curl_init')) {
            $handle = curl_init($probeUrl);

            curl_setopt_array($handle, [
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT_MS => 300,
                CURLOPT_TIMEOUT_MS => 500,
                CURLOPT_FOLLOWLOCATION => false,
            ]);

            curl_exec($handle);

            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);

            // Dev server Vite membalas 200 untuk /@vite/client.
            return $status === 200;
        }

        return $this->canConnect($url);
    }

    /**
     * Fallback bila ekstensi cURL tidak tersedia: cek koneksi TCP ke host:port.
     */
    protected function canConnect(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return false;
        }

        $secure = ($parts['scheme'] ?? 'http') === 'https';
        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? ($secure ? 443 : 80);

        // IPv6 perlu dibungkus kurung siku pada alamat transport PHP.
        if (str_contains($host, ':')) {
            $host = '['.$host.']';
        }

        $socket = @stream_socket_client(
            ($secure ? 'ssl://' : 'tcp://').$host.':'.$port,
            $errorCode,
            $errorMessage,
            0.3
        );

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
