<?php

namespace App\Services;

use App\Exceptions\ShippingException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OsmRoutingService
{
    private const PUBLIC_OSRM_HOST = 'router.project-osrm.org';

    /**
     * Hitung jarak dan durasi perjalanan darat antara dua koordinat.
     *
     * @return array{jarak_km: float, durasi_menit: int, provider: string}
     */
    public function route(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): array {
        $this->assertCoordinates($originLatitude, $originLongitude);
        $this->assertCoordinates($destinationLatitude, $destinationLongitude);
        $this->assertProviderAllowed();

        $cacheKey = sprintf(
            'osm:route:%s,%s;%s,%s',
            number_format($originLatitude, 7, '.', ''),
            number_format($originLongitude, 7, '.', ''),
            number_format($destinationLatitude, 7, '.', ''),
            number_format($destinationLongitude, 7, '.', ''),
        );

        return Cache::remember(
            $cacheKey,
            (int) config('services.osm.route_cache_ttl', 604800),
            fn (): array => $this->requestRoute(
                $originLatitude,
                $originLongitude,
                $destinationLatitude,
                $destinationLongitude
            )
        );
    }

    /**
     * @return array{jarak_km: float, durasi_menit: int, provider: string}
     */
    private function requestRoute(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): array {
        $url = sprintf(
            '%s/route/v1/driving/%s,%s;%s,%s',
            rtrim((string) config('services.osm.osrm_url'), '/'),
            number_format($originLongitude, 7, '.', ''),
            number_format($originLatitude, 7, '.', ''),
            number_format($destinationLongitude, 7, '.', ''),
            number_format($destinationLatitude, 7, '.', ''),
        );

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('services.osm.user_agent'),
                'Accept' => 'application/json',
            ])
                ->connectTimeout((int) config('services.osm.connect_timeout', 3))
                ->timeout((int) config('services.osm.timeout', 8))
                ->get($url, [
                    'overview' => 'false',
                    'alternatives' => 'false',
                    'steps' => 'false',
                ]);
        } catch (ConnectionException) {
            throw new ShippingException(
                'Layanan perhitungan jarak pengiriman tidak dapat dihubungi.',
                502,
                'SHIPPING_ROUTING_UNAVAILABLE'
            );
        } catch (Throwable $e) {
            report($e);

            throw new ShippingException(
                'Layanan perhitungan jarak pengiriman gagal memproses permintaan.',
                502,
                'SHIPPING_ROUTING_FAILED'
            );
        }

        if (! $response->successful()) {
            if ($response->status() === 429) {
                throw new ShippingException(
                    'Layanan perhitungan jarak sedang membatasi permintaan. Coba lagi beberapa saat.',
                    429,
                    'SHIPPING_ROUTING_RATE_LIMITED'
                );
            }

            throw new ShippingException(
                'Layanan perhitungan jarak pengiriman sedang tidak tersedia.',
                502,
                'SHIPPING_ROUTING_UNAVAILABLE'
            );
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['code'] ?? null) !== 'Ok') {
            throw new ShippingException(
                'Rute pengiriman tidak ditemukan untuk alamat tersebut.',
                422,
                'SHIPPING_ROUTE_NOT_FOUND'
            );
        }

        $routes = $payload['routes'] ?? null;
        $route = is_array($routes) ? ($routes[0] ?? null) : null;

        if (! is_array($route) || ! isset($route['distance']) || ! is_numeric($route['distance'])) {
            throw new ShippingException(
                'Respons layanan perhitungan jarak tidak valid.',
                502,
                'SHIPPING_ROUTING_INVALID_RESPONSE'
            );
        }

        $distanceMeters = (float) $route['distance'];

        if ($distanceMeters < 0) {
            throw new ShippingException(
                'Jarak pengiriman tidak valid.',
                502,
                'SHIPPING_ROUTING_INVALID_RESPONSE'
            );
        }

        $durationSeconds = isset($route['duration']) && is_numeric($route['duration'])
            ? (float) $route['duration']
            : 0.0;

        return [
            'jarak_km' => round($distanceMeters / 1000, 3),
            'durasi_menit' => (int) ceil($durationSeconds / 60),
            'provider' => 'OSRM',
        ];
    }

    private function assertCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new ShippingException(
                'Koordinat alamat pengiriman tidak valid.',
                422,
                'SHIPPING_INVALID_COORDINATES'
            );
        }
    }

    private function assertProviderAllowed(): void
    {
        if (! app()->environment('production') || (bool) config('services.osm.allow_public_demo', false)) {
            return;
        }

        $host = parse_url((string) config('services.osm.osrm_url'), PHP_URL_HOST);

        if ($host === self::PUBLIC_OSRM_HOST) {
            throw new ShippingException(
                'Layanan OSRM publik belum layak dipakai di production. Konfigurasikan OSM_OSRM_URL ke instance sendiri.',
                503,
                'SHIPPING_PROVIDER_NOT_CONFIGURED'
            );
        }
    }
}
