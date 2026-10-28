<?php

namespace App\Console\Commands;

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

    protected $description = 'Cek konektivitas endpoint Midtrans';

    public function handle(): int
    {
        $rows = [];
        $failed = 0;

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
