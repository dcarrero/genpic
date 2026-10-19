<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_cents_returns_price_es_cents(): void
    {
        $product = Product::factory()->create(['price_es_cents' => 1299]);
        $this->assertEquals(1299, $product->priceCents());
    }

    public function test_formatted_price_in_eur(): void
    {
        $product = Product::factory()->create(['price_es_cents' => 1299]);
        $this->assertEquals('12,99€', $product->formattedPrice());
    }

    public function test_scope_active_only_returns_active_products(): void
    {
        Product::factory()->create(['active' => true, 'sort_order' => 2]);
        Product::factory()->create(['active' => true, 'sort_order' => 1]);
        Product::factory()->inactive()->create();

        $active = Product::active()->get();
        $this->assertCount(2, $active);
        // Verificar orden
        $this->assertEquals(1, $active->first()->sort_order);
    }
}
