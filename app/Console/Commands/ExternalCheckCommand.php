<?php

namespace App\Console\Commands;

use App\Exceptions\RajaOngkirException;
use App\Services\RajaOngkirService;
use App\Support\CaBundle;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cek konektivitas seluruh endpoint pihak ketiga yang dipakai aplikasi.
 *
 * Read-only: tidak membuat/mengubah data. Keluar dengan kode 1 bila ada
 * endpoint yang benar-benar tidak bisa dihubungi/ditolak.
 */
class ExternalCheckCommand extends Command
{
    protected $signature = 'external:check';

    protected $description = 'Cek konektivitas endpoint RajaOngkir (destinations & cost) dan Midtrans';

    public function handle(RajaOngkirService $rajaOngkir): int
    {
        $rows = [];
        $failed = 0;

        $apiKey = (string) config('services.rajaongkir.api_key');
        $origin = $rajaOngkir->originId();

        if ($apiKey !== '' && $origin !== null) {
            $rows[] = ['RajaOngkir config', '<fg=green>OK</>', 'origin='.$origin];
        } else {
            $rows[] = ['RajaOngkir config', '<fg=red>GAGAL</>', 'RAJAONGKIR_API_KEY / RAJAONGKIR_ORIGIN_ID kosong'];
            $failed++;
        }

        try {
            $items = $rajaOngkir->destinations('surakarta');
            $rows[] = ['RajaOngkir destinations', '<fg=green>OK</>', count($items).' tujuan ditemukan'];
        } catch (RajaOngkirException $e) {
            $rows[] = ['RajaOngkir destinations', '<fg=red>GAGAL</>', $e->getMessage().' (HTTP '.$e->statusCode().')'];
            $failed++;
        }

        if ($origin !== null) {
            try {
                $costs = $rajaOngkir->cost($origin, 1000, 'jne');
                $first = $costs[0];
                $rows[] = ['RajaOngkir cost', '<fg=green>OK</>', count($costs).' layanan; '.$first['service'].'='.$first['cost']];
            } catch (RajaOngkirException $e) {
                $rows[] = ['RajaOngkir cost', '<fg=red>GAGAL</>', $e->getMessage().' (HTTP '.$e->statusCode().')'];
                $failed++;
            }
        }

        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            $rows[] = ['Midtrans API', '<fg=red>GAGAL</>', 'MIDTRANS_SERVER_KEY kosong'];
            $failed++;
        } else {
            $base = config('services.midtrans.is_production')
                ? 'https://api.midtrans.com'
                : 'https://api.sandbox.midtrans.com';

            try {
                $response = Http::withBasicAuth($serverKey, '')
                    ->withOptions(CaBundle::verifyOptions())
                    ->acceptJson()
                    ->connectTimeout(8)
                    ->timeout(15)
                    ->retry(2, 250, fn (Throwable $e) => $e instanceof ConnectionException, throw: false)
                    ->get($base.'/v2/cek-konektivitas-'.uniqid().'/status');

                $rows[] = ['Midtrans API', '<fg=green>OK</>', 'HTTP '.$response->status().' (terjangkau & terautentikasi)'];
            } catch (ConnectionException $e) {
                $rows[] = ['Midtrans API', '<fg=red>GAGAL</>', 'tidak dapat dihubungi'];
                $failed++;
            }
        }

        $this->newLine();
        $this->table(['Pemeriksaan', 'Status', 'Detail'], $rows);
        $this->newLine();

        if ($failed === 0) {
            $this->info('Semua endpoint eksternal dapat dihubungi.');

            return self::SUCCESS;
        }

        $this->error($failed.' pemeriksaan gagal.');

        return self::FAILURE;
    }
}
