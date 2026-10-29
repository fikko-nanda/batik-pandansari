<?php

namespace Tests\Feature\Services;

use App\Exceptions\ShippingException;
use App\Services\OsmGeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Feature\Endpoints\EndpointTestCase;

class OsmGeocodingServiceTest extends EndpointTestCase
{
    public function test_normalizes_and_caches_nominatim_results(): void
    {
        Http::preventStrayRequests();
        Cache::flush();
        config(['services.osm.nominatim_min_interval_seconds' => 0]);
        Http::fake([
            rtrim((string) config('services.osm.nominatim_url'), '/').'/search*' => Http::response([[
                'display_name' => 'Jembangan, Sragen, Jawa Tengah',
                'lat' => '-7.445',
                'lon' => '110.89',
                'address' => [
                    'state' => 'Jawa Tengah',
                    'county' => 'Sragen',
                    'village' => 'Jembangan',
                    'postcode' => '57283',
                ],
            ]]),
        ]);

        $service = app(OsmGeocodingService::class);
        $firstResult = $service->search('  Jembangan   Sragen  ');
        $cachedResult = $service->search('jembangan sragen');

        $this->assertSame($firstResult, $cachedResult);
        $this->assertSame(-7.445, $firstResult[0]['latitude']);
        $this->assertSame(110.89, $firstResult[0]['longitude']);
        $this->assertSame('Sragen', $firstResult[0]['nama_kota']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->data()['q'] === 'Jembangan Sragen');
    }

    public function test_waits_between_nominatim_provider_requests(): void
    {
        Http::preventStrayRequests();
        Cache::flush();
        Sleep::fake();
        config(['services.osm.nominatim_min_interval_seconds' => 1]);
        Http::fake([
            rtrim((string) config('services.osm.nominatim_url'), '/').'/search*' => Http::response([]),
        ]);

        $service = app(OsmGeocodingService::class);
        $service->search('Sragen pertama');
        $service->search('Sragen kedua');

        Sleep::assertSleptTimes(1);
        Http::assertSentCount(2);
    }

    public function test_returns_429_when_nominatim_rejects_the_request_rate(): void
    {
        Http::preventStrayRequests();
        config(['services.osm.nominatim_min_interval_seconds' => 0]);
        Http::fake([
            rtrim((string) config('services.osm.nominatim_url'), '/').'/search*' => Http::response([], 429),
        ]);

        try {
            app(OsmGeocodingService::class)->search('Sragen');
            $this->fail('Respons provider 429 harus dikembalikan sebagai error rate-limit.');
        } catch (ShippingException $exception) {
            $this->assertSame(429, $exception->statusCode());
            $this->assertSame('SHIPPING_GEOCODING_RATE_LIMITED', $exception->errorCode());
        }
    }

    public function test_returns_gateway_error_when_nominatim_times_out(): void
    {
        Http::preventStrayRequests();
        config(['services.osm.nominatim_min_interval_seconds' => 0]);
        Http::fake([
            rtrim((string) config('services.osm.nominatim_url'), '/').'/search*' => Http::failedConnection(),
        ]);

        try {
            app(OsmGeocodingService::class)->search('Sragen');
            $this->fail('Kegagalan koneksi provider harus dilaporkan.');
        } catch (ShippingException $exception) {
            $this->assertSame(502, $exception->statusCode());
            $this->assertSame('SHIPPING_GEOCODING_UNAVAILABLE', $exception->errorCode());
        }
    }

    public function test_blocks_public_nominatim_in_production_by_default(): void
    {
        Http::preventStrayRequests();
        $this->app->instance('env', 'production');
        config([
            'services.osm.nominatim_url' => 'https://nominatim.openstreetmap.org',
            'services.osm.allow_public_demo' => false,
        ]);

        try {
            app(OsmGeocodingService::class)->search('Sragen');
            $this->fail('Provider publik harus diblokir di production secara default.');
        } catch (ShippingException $exception) {
            $this->assertSame(503, $exception->statusCode());
            $this->assertSame('SHIPPING_PROVIDER_NOT_CONFIGURED', $exception->errorCode());
        }
    }
}
