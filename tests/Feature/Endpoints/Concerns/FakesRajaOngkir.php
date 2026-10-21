<?php

namespace Tests\Feature\Endpoints\Concerns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Fake respon API RajaOngkir (rajaongkir.komerce.id) untuk pengujian —
 * memakai Http::fake sehingga tidak ada panggilan jaringan/kuota asli.
 *
 * Satu stub dicatat sekali; respon berikutnya diubah lewat properti
 * $rajaOngkirStub sehingga aman dipanggil berulang dalam satu test
 * (stub pertama yang terdaftar selalu menang pada Http::fake).
 */
trait FakesRajaOngkir
{
    protected array $rajaOngkirStub = [];

    protected bool $rajaOngkirFaked = false;

    /**
     * @param  array{status?: int, body?: array<string, mixed>, throw?: bool}  $stub
     */
    protected function fakeRajaOngkir(array $stub): void
    {
        $this->rajaOngkirStub = $stub;

        if ($this->rajaOngkirFaked) {
            return;
        }

        $this->rajaOngkirFaked = true;

        Http::fake([
            'rajaongkir.komerce.id/*' => function () {
                if ($this->rajaOngkirStub['throw'] ?? false) {
                    throw new ConnectionException('cURL error 28: Connection timed out');
                }

                return Http::response(
                    $this->rajaOngkirStub['body'] ?? [],
                    $this->rajaOngkirStub['status'] ?? 200,
                );
            },
        ]);
    }

    protected function fakeRajaOngkirCost(int $cost = 20000, string $service = 'CTC', string $courier = 'jne'): void
    {
        $this->fakeRajaOngkir([
            'status' => 200,
            'body' => [
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'name' => strtoupper($courier),
                        'code' => $courier,
                        'service' => $service,
                        'description' => 'Layanan Reguler',
                        'cost' => $cost,
                        'etd' => '2-3',
                    ],
                    [
                        'name' => strtoupper($courier),
                        'code' => $courier,
                        'service' => 'YES',
                        'description' => 'Layanan Express',
                        'cost' => $cost + 15000,
                        'etd' => '1-2',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function fakeRajaOngkirDestinations(array $items): void
    {
        $this->fakeRajaOngkir([
            'status' => 200,
            'body' => [
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => $items,
            ],
        ]);
    }

    protected function fakeRajaOngkirError(int $metaCode, int $httpStatus, ?array $data = null): void
    {
        $this->fakeRajaOngkir([
            'status' => $httpStatus,
            'body' => [
                'meta' => ['message' => 'Error from RajaOngkir', 'code' => $metaCode, 'status' => 'error'],
                'data' => $data,
            ],
        ]);
    }

    protected function fakeRajaOngkirConnectionError(): void
    {
        $this->fakeRajaOngkir(['throw' => true]);
    }
}
