<?php

namespace Tests\Feature;

use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Idempotencia del checkout: reutilizar la order pending solo cuando es el mismo
 * intento de compra, nunca cuando cambia el producto o el importe.
 */
class CheckoutIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private GenerationSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => null]); // rama de pago simulado
        $style = ArtisticStyle::factory()->create(['active' => true]);
        $this->session = GenerationSession::factory()->create([
            'artistic_style_id' => $style->id,
            'expires_at'        => now()->addDays(14),
        ]);
    }

    private function product(string $key, int $cents): Product
    {
        return Product::factory()->create([
            'product_key'    => $key,
            'price_es_cents' => $cents,
            'price_en_cents' => $cents,
            'active'         => true,
        ]);
    }

    private function checkout(string $key): void
    {
        $this->post('/checkout/session', [
            'session_uuid' => $this->session->uuid,
            'product_key'  => $key,
            'email'        => 'compras@example.com',
        ]);
    }

    /** Doble submit del mismo producto: una sola order. */
    public function test_double_submit_reuses_the_pending_order(): void
    {
        $this->product('web_basic', 100);

        $this->checkout('web_basic');
        Order::first()->update(['status' => 'pending', 'stripe_checkout_id' => 'cs_a']);
        $this->checkout('web_basic');

        $this->assertSame(1, Order::count(), 'Un doble submit del mismo producto duplicó la order');
    }

    /** Cambiar de producto NO debe reutilizar la order anterior. */
    public function test_switching_product_does_not_reuse_the_previous_order(): void
    {
        $this->product('web_basic', 100);
        $this->product('pack_completo', 999);

        $this->checkout('web_basic');
        $first = Order::first();
        $first->update(['status' => 'pending', 'stripe_checkout_id' => 'cs_barato']);

        $this->checkout('pack_completo');

        $this->assertSame(2, Order::count(), 'Pedir otro producto reutilizó la order del anterior');

        $nueva = Order::where('id', '!=', $first->id)->first();
        $this->assertSame(999, $nueva->total_cents, 'La order nueva no tiene el importe del producto pedido');
        $this->assertSame(
            'pack_completo',
            $nueva->items()->where('is_upsell', false)->first()->product->product_key,
            'La order nueva no es del producto pedido'
        );
        $this->assertNull($nueva->stripe_checkout_id, 'La order nueva heredó el checkout de la anterior');
    }

    /** Una order pending sin checkout_id todavía debe reutilizarse (carrera). */
    public function test_pending_order_without_checkout_id_is_reused(): void
    {
        $this->product('web_basic', 100);

        $this->checkout('web_basic');
        Order::first()->update(['status' => 'pending', 'stripe_checkout_id' => null]);
        $this->checkout('web_basic');

        $this->assertSame(1, Order::count(), 'Una order pending sin checkout_id no se reconoció y se duplicó');
    }
}
