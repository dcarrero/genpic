<?php

namespace Tests\Feature;

use App\Jobs\GenerateFormatsJob;
use App\Models\Generation;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_loads(): void
    {
        $session = GenerationSession::factory()->watermarked()->create();
        Product::factory()->create();

        // Caso del bug: sin ?product= no debe dar 500 (regresión del TypeError con null)
        $this->get("/checkout/{$session->uuid}")->assertStatus(200);

        // Con ?product= válido también carga
        $product = Product::first();
        $this->get("/checkout/{$session->uuid}?product={$product->product_key}")->assertStatus(200);

        // Con ?product= inválido cae al primer producto activo, no 500
        $this->get("/checkout/{$session->uuid}?product=no-existe")->assertStatus(200);
    }

    public function test_create_session_validates_input(): void
    {
        $response = $this->postJson('/checkout/session');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_uuid', 'product_key', 'email']);
    }

    public function test_create_session_without_stripe_simulates_payment(): void
    {
        Queue::fake();

        // Sin Stripe configurado → simula pago
        config(['services.stripe.secret' => null]);

        $session = GenerationSession::factory()->watermarked()->create();
        Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);
        $product = Product::factory()->create(['price_es_cents' => 499]);

        $response = $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key' => $product->product_key,
            'email' => 'buyer@example.com',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'generation_session_id' => $session->id,
            'status' => 'paid',
            'total_cents' => 499,
            'currency' => 'eur',
        ]);

        Queue::assertPushed(GenerateFormatsJob::class);
    }

    public function test_success_page_loads(): void
    {
        $order = Order::factory()->paid()->create();

        $response = $this->get('/checkout/success?order='.$order->uuid);
        $response->assertStatus(200);
    }

    public function test_cancel_page_loads(): void
    {
        $order = Order::factory()->create();

        $response = $this->get('/checkout/cancel?order='.$order->uuid);
        $response->assertStatus(200);
    }
}
