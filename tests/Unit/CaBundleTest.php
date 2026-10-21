<?php

namespace Tests\Unit;

use App\Support\CaBundle;
use PHPUnit\Framework\TestCase;

class CaBundleTest extends TestCase
{
    public function test_path_eksplisit_dipakai_bila_file_ada(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cacert');

        try {
            $this->assertSame($file, CaBundle::path($file, true));
        } finally {
            @unlink($file);
        }
    }

    public function test_path_eksplisit_yang_tidak_ada_diabaikan(): void
    {
        $this->assertNull(CaBundle::path(sys_get_temp_dir().'/cacert-tidak-ada.pem', true));
    }

    public function test_tidak_pakai_fallback_saat_sistem_sudah_punya_ca(): void
    {
        $this->assertNull(CaBundle::path(null, true));
        $this->assertSame([], CaBundle::verifyOptions(null, true));
    }

    public function test_fallback_ke_cacert_bawaan_sdk_midtrans(): void
    {
        $path = CaBundle::path(null, false);

        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertStringContainsString('cacert.pem', $path);
    }

    public function test_verify_options_memakai_path_eksplisit(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cacert');

        try {
            $this->assertSame(['verify' => $file], CaBundle::verifyOptions($file, false));
        } finally {
            @unlink($file);
        }
    }
}
