<?php

namespace App\Support;

use Midtrans\Config;

/**
 * Penentu file CA (cacert.pem) untuk verifikasi TLS.
 *
 * Sebagian lingkungan (mis. Windows/Herd tanpa CA bundle) membuat cURL gagal
 * dengan "SSL certificate problem" (error 60). Resolver ini memakai CA bawaan
 * SDK Midtrans sebagai fallback agar aplikasi tidak bergantung pada php.ini
 * tiap mesin. Urutan prioritas:
 *
 * 1. Path eksplisit (env, mis. RAJAONGKIR_VERIFY) bila filenya ada.
 * 2. Bila PHP sudah punya CA (curl.cainfo / openssl.cafile) → null (pakai default).
 * 3. cacert.pem bawaan SDK Midtrans (bila filenya ada).
 * 4. null.
 */
class CaBundle
{
    public static function path(?string $explicit = null, ?bool $systemHasCa = null): ?string
    {
        if (is_string($explicit) && $explicit !== '' && is_file($explicit)) {
            return $explicit;
        }

        if ($systemHasCa ?? self::systemHasCa()) {
            return null;
        }

        $bundled = self::bundledPath();

        return $bundled !== null && is_file($bundled) ? $bundled : null;
    }

    /**
     * Opsi Guzzle untuk verifikasi TLS (kosong bila memakai default PHP).
     *
     * @return array<string, string>
     */
    public static function verifyOptions(?string $explicit = null, ?bool $systemHasCa = null): array
    {
        $path = self::path($explicit, $systemHasCa);

        return $path === null ? [] : ['verify' => $path];
    }

    public static function systemHasCa(): bool
    {
        return trim((string) ini_get('curl.cainfo')) !== ''
            || trim((string) ini_get('openssl.cafile')) !== '';
    }

    private static function bundledPath(): ?string
    {
        if (! class_exists(Config::class)) {
            return null;
        }

        $dir = dirname((new \ReflectionClass(Config::class))->getFileName());

        return $dir.'/../data/cacert.pem';
    }
}
