<?php

namespace App\Services;

use App\Exceptions\ShippingException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;

class OsmGeocodingService
{
    private const PUBLIC_NOMINATIM_HOST = 'nominatim.openstreetmap.org';

    private const LAST_REQUEST_CACHE_KEY = 'osm:nominatim-last-request-at';

    /**
     * Cari alamat Indonesia melalui Nominatim atau Photon dan normalisasi hasilnya.
     *
     * @return array<int, array{
     *     label: string,
     *     latitude: float,
     *     longitude: float,
     *     nama_provinsi: string|null,
     *     nama_kota: string|null,
     *     nama_kecamatan: string|null,
     *     kode_pos: string|null
     * }>
     */
    public function search(string $query): array
    {
        $query = trim((string) preg_replace('/\s+/u', ' ', $query));

        if (mb_strlen($query) < 3) {
            throw new ShippingException(
                'Kata kunci alamat minimal 3 karakter.',
                422,
                'SHIPPING_ADDRESS_QUERY_TOO_SHORT'
            );
        }

        $this->assertProviderAllowed();

        $cacheKey = 'osm:geocode:'.hash('sha256', mb_strtolower($query));

        return Cache::remember(
            $cacheKey,
            (int) config('services.osm.geocode_cache_ttl', 2592000),
            fn (): array => $this->requestSearch($query)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function requestSearch(string $query): array
    {
        // 1. Coba Nominatim terlebih dahulu
        try {
            $nominatimResults = $this->requestNominatim($query);
            if (! empty($nominatimResults)) {
                return $nominatimResults;
            }
        } catch (Throwable $e) {
            report($e);
        }

        // 2. Fallback ke Photon (OpenStreetMap search by Komoot) jika Nominatim bermasalah / timeout / rate-limited
        try {
            $photonResults = $this->requestPhoton($query);
            if (! empty($photonResults)) {
                return $photonResults;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return [];
    }

    /**
     * Pencarian via Nominatim OpenStreetMap
     *
     * @return array<int, array<string, mixed>>
     */
    private function requestNominatim(string $query): array
    {
        $minimumInterval = max(0, (int) config('services.osm.nominatim_min_interval_seconds', 1));
        $connectTimeout = max(3, (int) config('services.osm.connect_timeout', 8));
        $timeout = max(5, (int) config('services.osm.timeout', 12));

        try {
            $response = Cache::lock(
                'osm:nominatim-request',
                max(10, $minimumInterval + $connectTimeout + $timeout + 5)
            )->block(max(3, $minimumInterval + 1), function () use (
                $query,
                $minimumInterval,
                $connectTimeout,
                $timeout
            ) {
                $lastRequestAt = (float) Cache::get(self::LAST_REQUEST_CACHE_KEY, 0);
                $waitMilliseconds = (int) ceil(max(
                    0,
                    $lastRequestAt + $minimumInterval - microtime(true)
                ) * 1000);

                if ($waitMilliseconds > 0) {
                    Sleep::for($waitMilliseconds)->milliseconds();
                }

                Cache::put(
                    self::LAST_REQUEST_CACHE_KEY,
                    microtime(true),
                    max(1, $minimumInterval + 1)
                );

                $client = Http::withHeaders([
                    'User-Agent' => (string) config('services.osm.user_agent', 'BatikPandansariApp/1.0 (admin@batikpandansari.com)'),
                    'Accept' => 'application/json',
                ])
                    ->connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->retry(2, 200);

                $caBundle = config('services.osm.ca_bundle') ?: config('services.api.ca_bundle');
                $verify = config('services.osm.verify_ssl');

                if ($caBundle && file_exists($caBundle)) {
                    $client = $client->withOptions(['verify' => $caBundle]);
                } elseif ($verify === false || ($verify === null && (PHP_OS_FAMILY === 'Windows' || ! app()->environment('production')))) {
                    $client = $client->withoutVerifying();
                }

                $params = [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 5,
                    'countrycodes' => 'id',
                    'accept-language' => 'id',
                ];

                if ($email = config('services.osm.email')) {
                    $params['email'] = $email;
                }

                return $client->get(rtrim((string) config('services.osm.nominatim_url'), '/').'/search', $params);
            });
        } catch (LockTimeoutException) {
            return [];
        } catch (Throwable) {
            return [];
        }

        if (! $response || ! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        $results = [];

        foreach ($payload as $item) {
            if (! is_array($item) || ! isset($item['lat'], $item['lon'])) {
                continue;
            }

            if (! is_numeric($item['lat']) || ! is_numeric($item['lon'])) {
                continue;
            }

            $latitude = (float) $item['lat'];
            $longitude = (float) $item['lon'];

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                continue;
            }

            $address = is_array($item['address'] ?? null) ? $item['address'] : [];

            $results[] = [
                'label' => (string) ($item['display_name'] ?? ''),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'nama_provinsi' => $this->firstString($address, ['state']),
                'nama_kota' => $this->firstString($address, ['city', 'town', 'municipality', 'county']),
                'nama_kecamatan' => $this->firstString($address, ['suburb', 'village', 'district', 'neighbourhood']),
                'kode_pos' => $this->firstString($address, ['postcode']),
            ];
        }

        return $results;
    }

    /**
     * Pencarian fallback via Photon OpenStreetMap (Komoot)
     *
     * @return array<int, array<string, mixed>>
     */
    private function requestPhoton(string $query): array
    {
        $connectTimeout = max(3, (int) config('services.osm.connect_timeout', 8));
        $timeout = max(5, (int) config('services.osm.timeout', 12));
        $photonUrl = (string) config('services.osm.photon_url', 'https://photon.komoot.io/api');

        $client = Http::withHeaders([
            'User-Agent' => (string) config('services.osm.user_agent', 'BatikPandansariApp/1.0 (admin@batikpandansari.com)'),
            'Accept' => 'application/json',
        ])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry(2, 200);

        $caBundle = config('services.osm.ca_bundle') ?: config('services.api.ca_bundle');
        $verify = config('services.osm.verify_ssl');

        if ($caBundle && file_exists($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        } elseif ($verify === false || ($verify === null && (PHP_OS_FAMILY === 'Windows' || ! app()->environment('production')))) {
            $client = $client->withoutVerifying();
        }

        $response = $client->get(rtrim($photonUrl, '/'), [
            'q' => $query,
            'limit' => 5,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();
        $features = $payload['features'] ?? null;
        if (! is_array($features)) {
            return [];
        }

        $results = [];
        foreach ($features as $f) {
            if (! is_array($f)) {
                continue;
            }
            $coords = $f['geometry']['coordinates'] ?? null;
            if (! is_array($coords) || count($coords) < 2) {
                continue;
            }
            $lon = (float) $coords[0];
            $lat = (float) $coords[1];

            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                continue;
            }

            $props = is_array($f['properties'] ?? null) ? $f['properties'] : [];

            // Filter agar fokus alamat Indonesia jika countrycode tersedia
            $countryCode = strtolower((string) ($props['countrycode'] ?? ''));
            if ($countryCode !== '' && $countryCode !== 'id') {
                continue;
            }

            $name = $props['name'] ?? null;
            $district = $props['district'] ?? null;
            $city = $props['city'] ?? $props['county'] ?? null;
            $state = $props['state'] ?? null;
            $postcode = $props['postcode'] ?? null;
            $country = $props['country'] ?? 'Indonesia';

            $parts = array_filter([$name, $district, $city, $state, $postcode, $country]);
            $label = implode(', ', $parts);

            $results[] = [
                'label' => $label,
                'latitude' => $lat,
                'longitude' => $lon,
                'nama_provinsi' => $state ? (string) $state : null,
                'nama_kota' => $city ? (string) $city : null,
                'nama_kecamatan' => ($district ?: $name) ? (string) ($district ?: $name) : null,
                'kode_pos' => $postcode ? (string) $postcode : null,
            ];
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  array<int, string>  $keys
     */
    private function firstString(array $address, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($address[$key]) && is_string($address[$key]) && trim($address[$key]) !== '') {
                return trim($address[$key]);
            }
        }

        return null;
    }

    private function assertProviderAllowed(): void
    {
        if (! app()->environment('production') || (bool) config('services.osm.allow_public_demo', false)) {
            return;
        }

        $host = parse_url((string) config('services.osm.nominatim_url'), PHP_URL_HOST);

        if ($host === self::PUBLIC_NOMINATIM_HOST) {
            throw new ShippingException(
                'Layanan Nominatim publik belum layak dipakai di production. Konfigurasikan OSM_NOMINATIM_URL ke instance sendiri.',
                503,
                'SHIPPING_PROVIDER_NOT_CONFIGURED'
            );
        }
    }
}
