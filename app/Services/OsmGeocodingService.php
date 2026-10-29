<?php

namespace App\Services;

use App\Exceptions\ShippingException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;

class OsmGeocodingService
{
    private const PUBLIC_NOMINATIM_HOST = 'nominatim.openstreetmap.org';

    private const LAST_REQUEST_CACHE_KEY = 'osm:nominatim-last-request-at';

    /**
     * Cari alamat Indonesia melalui Nominatim dan normalisasi hasilnya.
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
        try {
            $minimumInterval = max(0, (int) config('services.osm.nominatim_min_interval_seconds', 1));
            $connectTimeout = max(1, (int) config('services.osm.connect_timeout', 3));
            $timeout = max(1, (int) config('services.osm.timeout', 8));

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

                return Http::withHeaders([
                    'User-Agent' => (string) config('services.osm.user_agent'),
                    'Accept' => 'application/json',
                ])
                    ->connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->get(rtrim((string) config('services.osm.nominatim_url'), '/').'/search', [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'limit' => 5,
                        'countrycodes' => 'id',
                        'accept-language' => 'id',
                    ]);
            });
        } catch (LockTimeoutException) {
            throw new ShippingException(
                'Layanan pencarian alamat sedang sibuk. Coba lagi beberapa saat.',
                429,
                'SHIPPING_GEOCODING_BUSY'
            );
        } catch (ConnectionException) {
            throw new ShippingException(
                'Layanan pencarian alamat tidak dapat dihubungi.',
                502,
                'SHIPPING_GEOCODING_UNAVAILABLE'
            );
        } catch (Throwable $e) {
            report($e);

            throw new ShippingException(
                'Layanan pencarian alamat gagal memproses permintaan.',
                502,
                'SHIPPING_GEOCODING_FAILED'
            );
        }

        if ($response->status() === 429) {
            throw new ShippingException(
                'Layanan pencarian alamat sedang membatasi permintaan. Coba lagi beberapa saat.',
                429,
                'SHIPPING_GEOCODING_RATE_LIMITED'
            );
        }

        if (! $response->successful()) {
            throw new ShippingException(
                'Layanan pencarian alamat sedang tidak tersedia.',
                502,
                'SHIPPING_GEOCODING_UNAVAILABLE'
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new ShippingException(
                'Respons layanan pencarian alamat tidak valid.',
                502,
                'SHIPPING_GEOCODING_INVALID_RESPONSE'
            );
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
