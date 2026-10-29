<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.api.url' => 'https://backend.test/api']);
    }

    public function test_proxies_product_image_with_ngrok_bypass_header(): void
    {
        Http::fake([
            '*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $response = $this->get('/media/products/foto.jpg');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('fake-image', $response->getContent());

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/storage/products/foto.jpg')
            && $request->hasHeader('ngrok-skip-browser-warning', 'true'));
    }

    public function test_proxies_cms_profil_image_to_storage_path(): void
    {
        Http::fake([
            '*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $response = $this->get('/media/cms-profil/logo.jpg');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('fake-image', $response->getContent());

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/storage/cms/profil/logo.jpg'));
    }

    public function test_rejects_non_image_extension_without_calling_backend(): void
    {
        Http::fake();

        $this->get('/media/products/foto.txt')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_rejects_non_image_response_from_backend(): void
    {
        Http::fake([
            '*' => Http::response('<html>ngrok warning</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->get('/media/products/foto.jpg')->assertStatus(502);
    }

    public function test_returns_not_found_when_backend_image_is_missing(): void
    {
        Http::fake([
            '*' => Http::response('', 404),
        ]);

        $this->get('/media/products/foto.jpg')->assertNotFound();
    }
}
