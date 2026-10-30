<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminShippingPageTest extends TestCase
{
    public function test_shipping_page_redirects_guest_to_login(): void
    {
        $this->withoutVite()
            ->get(route('admin.shipping'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_session_opens_shipping_page_and_sidebar_menu(): void
    {
        $this->withoutVite()
            ->withCookie('pandansari_panel', json_encode([
                'role' => 'admin',
                'exp' => now()->addHour()->getTimestamp(),
            ], JSON_THROW_ON_ERROR))
            ->get(route('admin.shipping'))
            ->assertOk()
            ->assertSee('Pengaturan Ongkir')
            ->assertSee('Zona Pengiriman &amp; Tarif', false)
            ->assertSee('/admin/shipping', false);
    }

    public function test_teknisi_session_cannot_open_shipping_page(): void
    {
        $this->withoutVite()
            ->withCookie('pandansari_panel', json_encode([
                'role' => 'teknisi',
                'exp' => now()->addHour()->getTimestamp(),
            ], JSON_THROW_ON_ERROR))
            ->get(route('admin.shipping'))
            ->assertRedirect(route('login'));
    }
}
