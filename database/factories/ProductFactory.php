<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_key'    => fake()->unique()->slug(2),
            'name'           => ['es' => 'Producto ' . fake()->word(), 'en' => 'Product ' . fake()->word()],
            'description'    => ['es' => fake()->sentence(), 'en' => fake()->sentence()],
            'name_es'        => 'Producto ' . fake()->word(),
            'name_en'        => 'Product ' . fake()->word(),
            'description_es' => fake()->sentence(),
            'description_en' => fake()->sentence(),
            'price_es_cents' => 499,
            'price_en_cents' => 599,
            'type'           => 'digital',
            'sort_order'     => 0,
            'highlight'      => false,
            'active'         => true,
        ];
    }

    public function highlight(): static
    {
        return $this->state(['highlight' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
