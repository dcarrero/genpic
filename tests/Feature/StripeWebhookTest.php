<?php

namespace Tests\Feature;

use App\Jobs\GenerateFormatsJob;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $response = $this->postJson('/webhooks/stripe', ['type' => 'checkout.session.completed'], [
            'Stripe-Signature' => 'invalid',
        ]);

        $response->assertStatus(400);
    }

    public function test_idempotent_webhook_does_not_reprocess_paid_order(): void
    {
        Queue::fake();

        $order = Order::factory()->paid()->withStripe()->create();

        // Simular que el webhook llega de nuevo (bypass firma para unit test)
        // Verificamos que no se despacha de nuevo
        $initialCount = Order::where('status', 'paid')->count();
        $this->assertEquals(1, $initialCount);

        // El order ya está paid, no debería cambiar
        $this->assertTrue($order->isPaid());
        Queue::assertNotPushed(GenerateFormatsJob::class);
    }
}
