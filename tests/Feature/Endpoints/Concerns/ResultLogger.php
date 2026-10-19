<?php

namespace Tests\Feature\Endpoints\Concerns;

/**
 * Menulis setiap request/response pengujian endpoint ke berkas JSONL
 * agar dapat diproses menjadi laporan pengujian.
 */
class ResultLogger
{
    public static function path(): string
    {
        return dirname(__DIR__).'/results/hasil_pengujian.jsonl';
    }

    public static function reset(): void
    {
        $path = self::path();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        if (is_file($path)) {
            unlink($path);
        }

        touch($path);
    }

    public static function write(array $entry): void
    {
        $path = self::path();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents(
            $path,
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
