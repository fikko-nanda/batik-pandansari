<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    /**
     * Cookie panel disimpan terenkripsi oleh middleware EncryptCookies.
     * Helper ini mengembalikan payload mentah; test client akan mengenkripsinya.
     *
     * @return array<string, string>
     */
    private function panelCookie(string $role, int $expiresIn = 3600): array
    {
        return [
            'pandansari_panel' => json_encode([
                'role' => $role,
                'exp' => time() + $expiresIn,
                'token' => hash('sha256', 'token-uji'),
            ], JSON_THROW_ON_ERROR),
        ];
    }

    public function test_security_headers_are_sent_and_php_version_is_hidden(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->assertHeaderMissing('X-Powered-By');

        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_csp_allows_vite_dev_assets_without_invalid_ipv6_source(): void
    {
        $this->app['env'] = 'local';

        $response = $this->get('http://localhost/');
        $response->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('[::1]', $csp);
        $this->assertStringContainsString('http://127.0.0.1:5173', $csp);

        preg_match('/style-src ([^;]+)/', $csp, $style);
        $this->assertNotEmpty($style, 'Direktif style-src tidak ditemukan.');
        $this->assertStringContainsString('http://127.0.0.1:5173', $style[1]);

        preg_match('/script-src ([^;]+)/', $csp, $script);
        $this->assertNotEmpty($script, 'Direktif script-src tidak ditemukan.');
        $this->assertStringContainsString('ws://127.0.0.1:5173', $script[1]);

        preg_match('/connect-src ([^;]+)/', $csp, $connect);
        $this->assertNotEmpty($connect, 'Direktif connect-src tidak ditemukan.');
        $this->assertStringContainsString('http://127.0.0.1:5173', $connect[1]);
        $this->assertStringContainsString('ws://127.0.0.1:5173', $connect[1]);
    }

    public function test_admin_pages_redirect_to_login_without_panel_cookie(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_teknisi_pages_redirect_to_login_without_panel_cookie(): void
    {
        $this->get('/teknisi')->assertRedirect('/login');
    }

    public function test_panel_cookie_with_wrong_role_is_rejected(): void
    {
        $this->withCookies($this->panelCookie('teknisi'))
            ->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_expired_panel_cookie_is_rejected(): void
    {
        $this->withCookies($this->panelCookie('admin', -60))
            ->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $this->withCookies($this->panelCookie('admin'))
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_teknisi_can_open_teknisi_pages(): void
    {
        $this->withCookies($this->panelCookie('teknisi'))
            ->get('/teknisi')
            ->assertOk();
    }
}
