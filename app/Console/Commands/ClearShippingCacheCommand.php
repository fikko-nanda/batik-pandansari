<?php

namespace App\Console\Commands;

use App\Services\ShippingConfigService;
use Illuminate\Console\Command;

/**
 * Bersihkan cache settings pengiriman (origin & kurir aktif).
 *
 * Dipakai untuk memulihkan nilai basi: `ShippingConfigService` menyimpan
 * hasil baca `settings` di cache dengan TTL pendek, sehingga bila row diubah
 * di luar API, nilai lama bisa sempat tertinggal sampai TTL habis. Perintah
 * ini memaksa pembacaan ulang dari DB/config.
 */
class ClearShippingCacheCommand extends Command
{
    protected $signature = 'shipping:clear-cache';

    protected $description = 'Bersihkan cache konfigurasi pengiriman (origin & kurir aktif)';

    public function handle(ShippingConfigService $shippingConfig): int
    {
        $shippingConfig->forgetCached();

        $this->info('Cache konfigurasi pengiriman berhasil dibersihkan.');
        $this->line('origin_id aktif : '.$shippingConfig->originId());
        $this->line('kurir aktif     : '.implode(', ', $shippingConfig->activeCouriers()));

        return self::SUCCESS;
    }
}
