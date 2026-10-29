<?php

namespace Tests\Feature\Endpoints;

use App\Models\UserAddress;
use App\Models\ZonaPengiriman;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ShippingEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Pengiriman';

    public function test_address_search_requires_authentication_and_returns_nominatim_results(): void
    {
        Http::preventStrayRequests();
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
        $this->asGuest();
        $this->hit('GET', '/api/shipping/address-search?q=Sragen', [
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/shipping/address-search?q=Jembangan', [
            'expected' => 200,
        ]);

        $result = $this->recorded[1]['response']['data'][0] ?? [];
        $this->assertSame(-7.445, $result['latitude'] ?? null);
        $this->assertSame(110.89, $result['longitude'] ?? null);
        $this->assertSame('Jembangan, Sragen, Jawa Tengah', $result['label'] ?? null);
        Http::assertSentCount(1);
        $this->assertRecorded();
    }

    public function test_address_search_is_rate_limited_per_authenticated_user(): void
    {
        $this->actingAsRole('pelanggan');
        config(['services.osm.search_per_minute' => 1]);
        Http::preventStrayRequests();
        Http::fake([
            rtrim((string) config('services.osm.nominatim_url'), '/').'/search*' => Http::response([]),
        ]);

        $this->hit('GET', '/api/shipping/address-search?q=Sragen%20satu', [
            'expected' => 200,
        ]);
        $this->hit('GET', '/api/shipping/address-search?q=Sragen%20dua', [
            'expected' => 429,
        ]);

        Http::assertSentCount(1);
        $this->assertRecorded();
    }

    public function test_address_store_persists_coordinates_and_rejects_partial_coordinates(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $payload = [
            'label_alamat' => 'Rumah',
            'nama_penerima' => 'Pelanggan Uji',
            'nomor_telepon' => '081234567890',
            'nama_provinsi' => 'Jawa Tengah',
            'nama_kota' => 'Sragen',
            'nama_kecamatan' => 'Plupuh',
            'kode_pos' => '57283',
            'alamat_lengkap' => 'Jembangan',
            'latitude' => -7.445,
            'longitude' => 110.89,
        ];

        $this->hit('POST', '/api/addresses', [
            'json' => $payload,
            'expected' => 201,
        ]);

        $addressId = $this->recorded[0]['response']['data']['id_user_address'] ?? null;
        $address = UserAddress::where('id_user_address', $addressId)
            ->where('id_user', $user->id_user)
            ->firstOrFail();
        $this->assertSame(-7.445, (float) $address->latitude);
        $this->assertSame(110.89, (float) $address->longitude);

        unset($payload['longitude']);
        $this->hit('POST', '/api/addresses', [
            'json' => $payload,
            'expected' => 422,
        ]);
        $this->assertArrayHasKey(
            'longitude',
            $this->recorded[1]['response']['errors'] ?? [],
            'Koordinat latitude dan longitude harus dikirim berpasangan.'
        );
        $this->assertSame(1, UserAddress::where('id_user', $user->id_user)->count());
        $this->assertRecorded();
    }

    public function test_quote_rejects_address_without_coordinates(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(3);
        $catalog['variant']->update(['stok_dipesan' => 3]);
        $this->addToCart($user, $catalog['variant'], 3);
        $this->seedShippingBaseline();
        $address = $this->makeAddress($user);
        $address->forceFill(['latitude' => null, 'longitude' => null])->save();
        Http::preventStrayRequests();

        $this->hit('POST', '/api/shipping/quote', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 422,
        ]);

        $this->assertSame(
            'SHIPPING_ADDRESS_COORDINATES_REQUIRED',
            $this->recorded[0]['response']['code'] ?? null
        );
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_quote_is_rate_limited_per_authenticated_user(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $this->addToCart($user, $catalog['variant']);
        $this->seedShippingBaseline();
        config(['services.osm.quote_per_minute' => 1]);
        Http::preventStrayRequests();
        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 5000, 'duration' => 900]],
            ]),
        ]);
        $address = $this->makeAddress($user);

        $this->hit('POST', '/api/shipping/quote', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 200,
        ]);
        $this->hit('POST', '/api/shipping/quote', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 429,
        ]);

        Http::assertSentCount(1);
        $this->assertRecorded();
    }

    public function test_quote_returns_road_distance_and_calculates_shipping_from_rounded_weight(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(5);
        $catalog['variant']->update(['berat_gram' => 1001]);
        $this->addToCart($user, $catalog['variant'], 2);
        $this->seedShippingBaseline(5.0);
        Cache::flush();
        $address = $this->makeAddress($user);

        $this->hit('POST', '/api/shipping/quote', [
            'json' => ['id_user_address' => $address->id_user_address],
            'expected' => 200,
        ]);

        $quote = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(5, $quote['jarak_km'] ?? null);
        $this->assertSame(3, $quote['berat_dibulatkan_kg'] ?? null);
        $this->assertSame(11000, $quote['biaya_pengiriman'] ?? null);
        $this->assertSame('Zona Uji', $quote['zona']['nama_zona'] ?? null);
        Http::assertSentCount(1);
        $this->assertRecorded();
    }

    public function test_admin_zone_management_allows_touching_ranges_and_rejects_overlap(): void
    {
        $this->actingAsRole('admin');
        $firstZone = [
            'nama_zona' => 'Zona Kota',
            'jarak_min_km' => 0,
            'jarak_maks_km' => 5,
            'biaya_dasar' => 5000,
            'biaya_per_kg' => 2000,
        ];
        $secondZone = [
            'nama_zona' => 'Zona Kabupaten',
            'jarak_min_km' => 5,
            'jarak_maks_km' => 10,
            'biaya_dasar' => 8000,
            'biaya_per_kg' => 3000,
        ];

        $this->hit('POST', '/api/admin/shipping/zones', [
            'json' => $firstZone,
            'expected' => 201,
        ]);
        $firstId = $this->recorded[0]['response']['data']['id_zona'] ?? '';
        $this->hit('POST', '/api/admin/shipping/zones', [
            'json' => $secondZone,
            'expected' => 201,
        ]);
        $this->hit('POST', '/api/admin/shipping/zones', [
            'json' => array_merge($secondZone, [
                'nama_zona' => 'Zona Tumpang Tindih',
                'jarak_min_km' => 4,
                'jarak_maks_km' => 6,
            ]),
            'expected' => 422,
        ]);
        $this->hit('PUT', '/api/admin/shipping/zones/'.$firstId, [
            'json' => ['biaya_dasar' => 6000],
            'expected' => 200,
        ]);
        $this->hit('GET', '/api/admin/shipping/zones', [
            'expected' => 200,
        ]);

        $this->assertSame(2, ZonaPengiriman::count());
        $this->assertSame(6000.0, (float) ZonaPengiriman::findOrFail($firstId)->biaya_dasar);
        $this->assertCount(2, $this->recorded[4]['response']['data'] ?? []);
        $this->assertRecorded();
    }

    public function test_non_admin_cannot_manage_shipping_zones(): void
    {
        $this->actingAsRole('pelanggan');

        $this->hit('POST', '/api/admin/shipping/zones', [
            'json' => [
                'nama_zona' => 'Zona Terlarang',
                'jarak_min_km' => 0,
                'jarak_maks_km' => 10,
            ],
            'expected' => 403,
        ]);

        $this->assertSame(0, ZonaPengiriman::count());
        $this->assertRecorded();
    }
}
