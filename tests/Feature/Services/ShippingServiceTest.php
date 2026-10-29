<?php

namespace Tests\Feature\Services;

use App\Exceptions\ShippingException;
use App\Models\ProfilUsaha;
use App\Models\UserAddress;
use App\Models\ZonaPengiriman;
use App\Services\ShippingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Feature\Endpoints\EndpointTestCase;

class ShippingServiceTest extends EndpointTestCase
{
    public function test_calculates_cost_from_base_fee_and_ceiling_billed_kilograms(): void
    {
        $this->seedShippingBaseline();
        $shipping = app(ShippingService::class);

        $expected = [
            1 => [1, 7000.0],
            1000 => [1, 7000.0],
            1001 => [2, 9000.0],
            2000 => [2, 9000.0],
            2001 => [3, 11000.0],
        ];

        foreach ($expected as $weightGram => [$billedKg, $shippingCost]) {
            $quote = $shipping->quoteForDistance(['jarak_km' => 5.0], $weightGram);

            $this->assertSame($billedKg, $quote['berat_dibulatkan_kg']);
            $this->assertSame($shippingCost, $quote['biaya_pengiriman']);
        }
    }

    public function test_uses_inclusive_minimum_and_exclusive_maximum_zone_boundaries(): void
    {
        $this->createZone('Zona Pertama', 0, 5);
        $this->createZone('Zona Kedua', 5, 10);
        $shipping = app(ShippingService::class);

        $minimumQuote = $shipping->quoteForDistance(['jarak_km' => 0.0], 500);
        $boundaryQuote = $shipping->quoteForDistance(['jarak_km' => 5.0], 500);

        $this->assertSame('Zona Pertama', $minimumQuote['zona']['nama_zona']);
        $this->assertSame('Zona Kedua', $boundaryQuote['zona']['nama_zona']);

        try {
            $shipping->quoteForDistance(['jarak_km' => 10.0], 500);
            $this->fail('Jarak tepat pada batas maksimum seharusnya tidak cocok dengan zona.');
        } catch (ShippingException $exception) {
            $this->assertSame('SHIPPING_ZONE_NOT_FOUND', $exception->errorCode());
            $this->assertSame(422, $exception->statusCode());
            $this->assertStringContainsString('Zona pengiriman', $exception->getMessage());
        }
    }

    public function test_rejects_non_positive_package_weight(): void
    {
        $this->seedShippingBaseline();

        try {
            app(ShippingService::class)->quoteForDistance(['jarak_km' => 5.0], 0);
            $this->fail('Berat paket nol tidak boleh dihitung.');
        } catch (ShippingException $exception) {
            $this->assertSame('SHIPPING_INVALID_WEIGHT', $exception->errorCode());
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function test_requires_profile_one_with_origin_coordinates(): void
    {
        Http::preventStrayRequests();
        ProfilUsaha::create([
            'id_profil_usaha' => 2,
            'nama_usaha' => 'Profil Lain',
            'latitude' => -7.445,
            'longitude' => 110.89,
        ]);
        $address = new UserAddress([
            'latitude' => -7.5,
            'longitude' => 110.8,
        ]);

        try {
            app(ShippingService::class)->routeForAddress($address);
            $this->fail('Profil usaha dengan ID 1 dan koordinat wajib tersedia.');
        } catch (ShippingException $exception) {
            $this->assertSame('SHIPPING_ORIGIN_NOT_CONFIGURED', $exception->errorCode());
            $this->assertSame(503, $exception->statusCode());
        }
    }

    public function test_rejects_profile_one_without_origin_coordinates(): void
    {
        Http::preventStrayRequests();
        ProfilUsaha::create([
            'id_profil_usaha' => 1,
            'nama_usaha' => 'Batik Pandansari',
            'latitude' => null,
            'longitude' => null,
        ]);
        $address = new UserAddress([
            'latitude' => -7.5,
            'longitude' => 110.8,
        ]);

        try {
            app(ShippingService::class)->routeForAddress($address);
            $this->fail('Profil usaha ID 1 harus memiliki koordinat asal.');
        } catch (ShippingException $exception) {
            $this->assertSame('SHIPPING_ORIGIN_NOT_CONFIGURED', $exception->errorCode());
            $this->assertSame(503, $exception->statusCode());
        }
    }

    public function test_requires_destination_coordinates(): void
    {
        Http::preventStrayRequests();
        ProfilUsaha::create([
            'id_profil_usaha' => 1,
            'nama_usaha' => 'Batik Pandansari',
            'latitude' => -7.445,
            'longitude' => 110.89,
        ]);
        $address = new UserAddress;

        try {
            app(ShippingService::class)->routeForAddress($address);
            $this->fail('Koordinat tujuan wajib tersedia.');
        } catch (ShippingException $exception) {
            $this->assertSame('SHIPPING_ADDRESS_COORDINATES_REQUIRED', $exception->errorCode());
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function test_preserves_routing_precision_when_matching_zone_boundary(): void
    {
        $this->seedShippingBaseline(9.999, ['jarak_maks_km' => 10]);
        $user = $this->makeUser();
        $address = $this->makeAddress($user);

        $quote = app(ShippingService::class)->quoteForAddress($address, 500);

        $this->assertSame(9.999, $quote['jarak_km']);
        $this->assertSame('Zona Uji', $quote['zona']['nama_zona']);
    }

    private function createZone(string $name, float $minimumKm, float $maximumKm): ZonaPengiriman
    {
        return ZonaPengiriman::create([
            'id_zona' => (string) Str::uuid(),
            'nama_zona' => $name,
            'jarak_min_km' => $minimumKm,
            'jarak_maks_km' => $maximumKm,
            'biaya_dasar' => 5000,
            'biaya_per_kg' => 2000,
            'status_aktif' => true,
        ]);
    }
}
