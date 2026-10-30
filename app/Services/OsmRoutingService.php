<?php

namespace App\Services;

use App\Exceptions\ShippingException;
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
            $client = Http::withHeaders([
                'User-Agent' => (string) config('services.osm.user_agent', 'BatikPandansariApp/1.0 (admin@batikpandansari.com)'),
                'Accept' => 'application/json',
            ])
                ->connectTimeout((int) config('services.osm.connect_timeout', 8))
                ->timeout((int) config('services.osm.timeout', 12))
                ->retry(2, 200);

            $caBundle = config('services.osm.ca_bundle') ?: config('services.api.ca_bundle');
            $verify = config('services.osm.verify_ssl');

            if ($caBundle && file_exists($caBundle)) {
                $client = $client->withOptions(['verify' => $caBundle]);
            } elseif ($verify === false || ($verify === null && (PHP_OS_FAMILY === 'Windows' || ! app()->environment('production')))) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get($url, [
                'overview' => 'false',
                'alternatives' => 'false',
                'steps' => 'false',
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->fallbackHaversineRoute($originLatitude, $originLongitude, $destinationLatitude, $destinationLongitude);
        }

        if (! $response->successful()) {
            return $this->fallbackHaversineRoute($originLatitude, $originLongitude, $destinationLatitude, $destinationLongitude);
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

    private function fallbackHaversineRoute(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): array {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($destinationLatitude - $originLatitude);
        $dLon = deg2rad($destinationLongitude - $originLongitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($originLatitude)) * cos(deg2rad($destinationLatitude)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $jarakKm = max(0.5, round($earthRadiusKm * $c * 1.3, 3));
        $durasiMenit = (int) ceil($jarakKm * 2);

        return [
            'jarak_km' => $jarakKm,
            'durasi_menit' => $durasiMenit,
            'provider' => 'Estimasi Jarak Darat (Fallback)',
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
