<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kegagalan layanan pengiriman yang aman ditampilkan ke klien.
 *
 * - 422: input tidak dapat diproses (koordinat kosong, jarak di luar zona).
 * - 429: permintaan ke penyedia sedang dibatasi.
 * - 502: penyedia geocoding/routing tidak dapat dihubungi atau responsnya rusak.
 * - 503: konfigurasi asal/providers belum siap.
 */
class ShippingException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422,
        private readonly string $errorCode = 'SHIPPING_ERROR',
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
