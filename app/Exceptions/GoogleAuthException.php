<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error integrasi OAuth Google (verifikasi ID Token).
 *
 * statusCode adalah HTTP status yang harus dikembalikan ke klien:
 * - 502 : layanan Google tidak dapat dihubungi (timeout/dns/refused)
 * - 503 : konfigurasi GOOGLE_CLIENT_ID belum diisi
 */
class GoogleAuthException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 502,
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
