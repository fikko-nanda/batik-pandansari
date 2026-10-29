<?php

namespace App\Services;

use App\Exceptions\ShippingException;
use App\Models\ProfilUsaha;
use App\Models\UserAddress;
use App\Models\ZonaPengiriman;

class ShippingService
{
    private const ORIGIN_PROFILE_ID = 1;

    public function __construct(private readonly OsmRoutingService $routing) {}

    /**
     * Rute dari lokasi usaha ke alamat pelanggan.
     *
     * @return array{
     *     jarak_km: float,
     *     durasi_menit: int,
     *     provider: string,
     *     asal: array<string, mixed>,
     *     tujuan: array<string, mixed>
     * }
     */
    public function routeForAddress(UserAddress $address): array
    {
        $origin = $this->origin();

        $this->assertAddressCoordinates($address);

        $route = $this->routing->route(
            (float) $origin->latitude,
            (float) $origin->longitude,
            (float) $address->latitude,
            (float) $address->longitude
        );

        return $route + [
            'asal' => [
                'nama_usaha' => $origin->nama_usaha,
                'alamat_lengkap' => $origin->alamat_lengkap,
                'latitude' => (float) $origin->latitude,
                'longitude' => (float) $origin->longitude,
            ],
            'tujuan' => [
                'id_user_address' => $address->id_user_address,
                'label_alamat' => $address->label_alamat,
                'nama_penerima' => $address->nama_penerima,
                'alamat_lengkap' => $address->alamat_lengkap,
                'nama_kota' => $address->nama_kota,
                'latitude' => (float) $address->latitude,
                'longitude' => (float) $address->longitude,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quoteForAddress(UserAddress $address, int $beratGram): array
    {
        return $this->quoteForDistance($this->routeForAddress($address), $beratGram);
    }

    /**
     * Hitung zona dan biaya tanpa memanggil provider eksternal.
     *
     * Dipanggil kembali di dalam transaksi checkout memakai rute yang sudah
     * didapat sebelum transaksi dimulai, agar koneksi HTTP tidak menahan lock DB.
     *
     * @param  array<string, mixed>  $route
     * @return array<string, mixed>
     */
    public function quoteForDistance(array $route, int $beratGram): array
    {
        if ($beratGram <= 0) {
            throw new ShippingException(
                'Berat paket pengiriman tidak valid.',
                422,
                'SHIPPING_INVALID_WEIGHT'
            );
        }

        $jarakKm = (float) ($route['jarak_km'] ?? 0);
        $zona = ZonaPengiriman::untukJarak($jarakKm);

        if (! $zona) {
            throw new ShippingException(
                sprintf('Zona pengiriman untuk jarak %.2f km belum diatur.', $jarakKm),
                422,
                'SHIPPING_ZONE_NOT_FOUND'
            );
        }

        $kg = ZonaPengiriman::beratDibulatkanKg($beratGram);

        return [
            'jarak_km' => $jarakKm,
            'durasi_menit' => (int) ($route['durasi_menit'] ?? 0),
            'provider' => (string) ($route['provider'] ?? 'OSRM'),
            'berat_gram' => $beratGram,
            'berat_dibulatkan_kg' => $kg,
            'biaya_pengiriman' => $zona->biayaUntuk($beratGram),
            'zona' => [
                'id_zona' => $zona->id_zona,
                'nama_zona' => $zona->nama_zona,
                'jarak_min_km' => (float) $zona->jarak_min_km,
                'jarak_maks_km' => (float) $zona->jarak_maks_km,
                'biaya_dasar' => (float) $zona->biaya_dasar,
                'biaya_per_kg' => (float) $zona->biaya_per_kg,
            ],
            'asal' => $route['asal'] ?? null,
            'tujuan' => $route['tujuan'] ?? null,
        ];
    }

    public function origin(): ProfilUsaha
    {
        $profil = ProfilUsaha::find(self::ORIGIN_PROFILE_ID);

        if (! $profil
            || $profil->latitude === null
            || $profil->longitude === null
            || (string) $profil->latitude === ''
            || (string) $profil->longitude === '') {
            throw new ShippingException(
                'Lokasi asal pengiriman belum dikonfigurasi oleh admin.',
                503,
                'SHIPPING_ORIGIN_NOT_CONFIGURED'
            );
        }

        return $profil;
    }

    private function assertAddressCoordinates(UserAddress $address): void
    {
        if ($address->latitude === null
            || $address->longitude === null
            || (string) $address->latitude === ''
            || (string) $address->longitude === '') {
            throw new ShippingException(
                'Alamat pengiriman belum memiliki koordinat. Pilih alamat melalui pencarian OSM.',
                422,
                'SHIPPING_ADDRESS_COORDINATES_REQUIRED'
            );
        }
    }
}
