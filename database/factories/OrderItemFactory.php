<?php

namespace Database\Factories;

use App\Models\Generation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id'        => Order::factory(),
            'product_id'      => Product::factory(),
            'generation_id'   => Generation::factory(),
            'quantity'         => 1,
            'unit_price_cents' => 499,
            'total_cents'     => 499,
        ];
    }

    public function withExports(): static
    {
        return $this->state([
            'export_paths'  => ['web_basic' => 'exports/test/web_basic_abc.jpg'],
            'exports_ready' => true,
        ]);
    }
}
