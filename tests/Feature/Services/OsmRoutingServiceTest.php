<?php

namespace Tests\Feature\Services;

use App\Exceptions\ShippingException;
use App\Services\OsmRoutingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Endpoints\EndpointTestCase;

class OsmRoutingServiceTest extends EndpointTestCase
{
    public function test_does_not_reuse_route_cache_for_distinct_seven_decimal_coordinates(): void
    {
        Http::preventStrayRequests();
        Cache::flush();
        $requestCount = 0;
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => function () use (&$requestCount) {
                $requestCount++;

                return Http::response([
                    'code' => 'Ok',
                    'routes' => [[
                        'distance' => $requestCount * 1000,
                        'duration' => 120,
                    ]],
                ]);
            },
        ]);
        $service = app(OsmRoutingService::class);

        $firstRoute = $service->route(-7.445001, 110.890001, -7.5, 110.8);
        $cachedRoute = $service->route(-7.445001, 110.890001, -7.5, 110.8);
        $secondRoute = $service->route(-7.445002, 110.890002, -7.5, 110.8);

        $this->assertSame(1.0, $firstRoute['jarak_km']);
        $this->assertSame($firstRoute, $cachedRoute);
        $this->assertSame(2.0, $secondRoute['jarak_km']);
        Http::assertSentCount(2);
    }

    public function test_returns_provider_rate_limit_as_429(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => Http::response([], 429),
        ]);

        try {
            app(OsmRoutingService::class)->route(-7.445, 110.89, -7.5, 110.8);
            $this->fail('Respons provider 429 harus dikembalikan sebagai error rate-limit.');
        } catch (ShippingException $exception) {
            $this->assertSame(429, $exception->statusCode());
            $this->assertSame('SHIPPING_ROUTING_RATE_LIMITED', $exception->errorCode());
        }
    }

    public function test_returns_gateway_error_when_osrm_times_out(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => Http::failedConnection(),
        ]);

        try {
            app(OsmRoutingService::class)->route(-7.445, 110.89, -7.5, 110.8);
            $this->fail('Kegagalan koneksi provider harus dilaporkan.');
        } catch (ShippingException $exception) {
            $this->assertSame(502, $exception->statusCode());
            $this->assertSame('SHIPPING_ROUTING_UNAVAILABLE', $exception->errorCode());
        }
    }

    public function test_blocks_public_osrm_in_production_by_default(): void
    {
        Http::preventStrayRequests();
        $this->app->instance('env', 'production');
        config([
            'services.osm.osrm_url' => 'https://router.project-osrm.org',
            'services.osm.allow_public_demo' => false,
        ]);

        try {
            app(OsmRoutingService::class)->route(-7.445, 110.89, -7.5, 110.8);
            $this->fail('Provider publik harus diblokir di production secara default.');
        } catch (ShippingException $exception) {
            $this->assertSame(503, $exception->statusCode());
            $this->assertSame('SHIPPING_PROVIDER_NOT_CONFIGURED', $exception->errorCode());
        }
    }
}
