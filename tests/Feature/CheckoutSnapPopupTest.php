<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Halaman /checkout harus memuat Midtrans Snap SDK di head dengan client-key
 * asli dan membuka pembayaran lewat pop-up (Embed Mode) tanpa pengalihan
 * halaman ke redirect_url (alur lama).
 */
class CheckoutSnapPopupTest extends TestCase
{
    public function test_checkout_page_loads_midtrans_snap_sdk_with_real_client_key(): void
    {
        $this->withoutVite()
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('app.sandbox.midtrans.com/snap/snap.js', false)
            ->assertSee('data-client-key="', false)
            ->assertDontSee('data-client-key="SB-Mid-client-sample"', false);
    }

    public function test_checkout_opens_snap_popup_without_redirect_fallback(): void
    {
        $this->withoutVite()
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('PandansariAPI.bayarSnap(', false)
            ->assertSee('redirectFallback: false', false)
            ->assertDontSee('redirect_url', false);
    }
}
