<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error integrasi RajaOngkir (rajaongkir.komerce.id).
 *
 * statusCode adalah HTTP status yang harus dikembalikan ke klien:
 * - 422 : request tidak valid menurut RajaOngkir (param salah, dst.)
 * - 429 : kuota harian RajaOngkir habis
 * - 502 : upstream error / respons tidak wajar / API key bermasalah
 * - 503 : koneksi ke RajaOngkir gagal (timeout/dns/refused)
 */
class RajaOngkirException extends RuntimeException
{
    /**
     * Pesan ramah pengguna untuk tiap kode error. Kode mentah tetap dipakai
     * sebagai fallback bila tidak ada padanan.
     */
    private const MESSAGES = [
        'RAJAONGKIR_NOT_CONFIGURED' => 'Konfigurasi RajaOngkir belum lengkap (API key/origin belum diisi).',
        'RAJAONGKIR_QUOTA_EXCEEDED' => 'Kuota harian RajaOngkir sudah habis. Silakan coba lagi besok.',
        'RAJAONGKIR_UNAUTHORIZED' => 'API key RajaOngkir tidak valid atau ditolak.',
        'RAJAONGKIR_UPSTREAM_ERROR' => 'Layanan RajaOngkir sedang bermasalah. Silakan coba lagi nanti.',
        'RAJAONGKIR_INVALID_RESPONSE' => 'Respons RajaOngkir tidak dapat diproses.',
        'RAJAONGKIR_INVALID_REQUEST' => 'Permintaan pengiriman tidak valid.',
        'RAJAONGKIR_DESTINATION_NOT_FOUND' => 'Tujuan pengiriman tidak ditemukan atau kurir tidak melayani rute ini.',
        'RAJAONGKIR_NO_SERVICE' => 'Tidak ada layanan pengiriman yang tersedia untuk rute ini.',
        'RAJAONGKIR_UNAVAILABLE' => 'Layanan RajaOngkir tidak dapat dihubungi. Silakan coba lagi nanti.',
    ];

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

    /**
     * Pesan siap tampil untuk klien (Indonesia), bukan kode error mentah.
     */
    public function userMessage(): string
    {
        return self::MESSAGES[$this->getMessage()] ?? $this->getMessage();
    }
}
