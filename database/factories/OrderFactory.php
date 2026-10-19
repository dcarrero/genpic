<?php

namespace Database\Factories;

use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'generation_session_id' => GenerationSession::factory(),
            'subtotal_cents'        => 499,
            'total_cents'           => 499,
            'currency'              => 'EUR',
            'status'                => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state([
            'status'         => 'ready',
            'paid_at'        => now(),
            'download_ready' => true,
        ]);
    }

    public function withStripe(): static
    {
        return $this->state([
            'stripe_checkout_id'       => 'cs_test_' . fake()->uuid(),
            'stripe_payment_intent_id' => 'pi_test_' . fake()->uuid(),
            'stripe_customer_id'       => 'cus_test_' . fake()->uuid(),
        ]);
    }
}
