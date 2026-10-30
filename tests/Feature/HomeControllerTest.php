<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Endpoints\Concerns\CreatesApiSchema;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use CreatesApiSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApiSchema();
    }

    public function test_homepage_renders_only_active_products_matching_section_categories(): void
    {
        $kategoriWanita = $this->makeCategory('cat-wanita', 'Pakaian Wanita', 'pakaian-wanita');
        $kategoriPria = $this->makeCategory('cat-pria', 'Kemeja Pria', 'pakaian-pria');
        $kategoriSarimbit = $this->makeCategory('cat-sarimbit', 'Sarimbit Wanita', 'sarimbit-wanita');

        $dressWanita = $this->makeProduct('Dress Batik Wanita', $kategoriWanita->id_category);
        $kemejaPria = $this->makeProduct('Kemeja Batik Pria', $kategoriPria->id_category);
        $this->makeProduct('Sarimbit Couple Wanita', $kategoriSarimbit->id_category);
        $this->makeProduct('Dress Draft Wanita', $kategoriWanita->id_category, 'draft');

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame(
            [$dressWanita->id_product],
            $response->viewData('womensProducts')->pluck('id_product')->all()
        );
        $this->assertSame(
            [$kemejaPria->id_product],
            $response->viewData('mensProducts')->pluck('id_product')->all()
        );
    }

    public function test_homepage_falls_back_to_empty_collections_when_database_fails(): void
    {
        Exceptions::fake();

        Schema::drop('products');

        $response = $this->get('/');

        $response->assertOk()
            ->assertViewHas('womensProducts', fn ($products) => $products->isEmpty())
            ->assertViewHas('mensProducts', fn ($products) => $products->isEmpty());

        Exceptions::assertReported(QueryException::class);
    }

    private function makeCategory(string $id, string $nama, string $slug): Category
    {
        return Category::create([
            'id_category' => $id,
            'nama_kategori' => $nama,
            'slug' => $slug,
            'status_aktif' => true,
        ]);
    }

    private function makeProduct(string $nama, string $idCategory, string $status = 'aktif'): Product
    {
        $product = Product::create([
            'id_product' => (string) Str::uuid(),
            'id_category' => $idCategory,
            'nama_produk' => $nama,
            'slug' => Str::slug($nama).'-'.Str::lower(Str::random(5)),
            'deskripsi' => 'Deskripsi '.$nama,
            'status_produk' => $status,
        ]);

        $product->categories()->sync([$idCategory]);

        return $product;
    }
}
