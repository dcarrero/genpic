<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'             => strtoupper(fake()->unique()->lexify('TEST????')),
            'type'             => 'percent',
            'value'            => 20,
            'product_keys'     => null,
            'max_uses'         => null,
            'times_used'       => 0,
            'once_per_email'   => true,
            'first_time_only'  => false,
            'valid_from'       => null,
            'valid_until'      => null,
            'auto_generated'   => false,
            'active'           => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function expired(): static
    {
        return $this->state(['valid_until' => now()->subDay()]);
    }

    public function notYetValid(): static
    {
        return $this->state(['valid_from' => now()->addDay()]);
    }

    public function exhausted(): static
    {
        return $this->state(['max_uses' => 1, 'times_used' => 1]);
    }

    public function fixed(int $valueCents = 200): static
    {
        return $this->state(['type' => 'fixed', 'value' => $valueCents]);
    }

    public function forProduct(string ...$productKeys): static
    {
        return $this->state(['product_keys' => array_values($productKeys)]);
    }
}
