<?php

namespace Tests\Unit\Services;

use App\Jobs\GenerateFormatsJob;
use App\Jobs\GenerateUpsellPortraitJob;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderFulfillmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderFulfillmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderFulfillmentService();
    }

    // ─── caso base: pago confirmado ───────────────────────────────────────────

    public function test_confirm_payment_transiciona_order_a_paid(): void
    {
        Bus::fake();
        Queue::fake();
        Mail::fake();

        $order = Order::factory()->create(['status' => 'pending']);

        $resultado = $this->service->confirmPayment($order, 'pi_test_123', 'cus_test_456', 'test');

        $this->assertTrue($resultado);
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('pi_test_123', $order->stripe_payment_intent_id);
        $this->assertSame('cus_test_456', $order->stripe_customer_id);
        $this->assertSame('paid', $order->session->status);
    }

    // ─── idempotencia ─────────────────────────────────────────────────────────

    public function test_confirm_payment_idempotente_con_order_ya_paid(): void
    {
        Bus::fake();
        Queue::fake();
        Mail::fake();

        $order = Order::factory()->paid()->create();
        $paidAt = $order->paid_at;

        $resultado = $this->service->confirmPayment($order, 'pi_nuevo', null, 'test');

        $this->assertFalse($resultado);
        $order->refresh();
        $this->assertSame('paid', $order->status);
        // paid_at no debe cambiar
        $this->assertEquals($paidAt->timestamp, $order->paid_at->timestamp);
        // El PI nuevo no se escribe porque fue idempotente
        $this->assertNotSame('pi_nuevo', $order->stripe_payment_intent_id);
    }

    // ─── reconciliación Stripe ────────────────────────────────────────────────

    public function test_confirm_payment_reconcilia_importes_desde_checkout_session(): void
    {
        Bus::fake();
        Queue::fake();
        Mail::fake();

        $order = Order::factory()->create(['status' => 'pending']);

        $checkoutSession = (object) [
            'amount_total'  => 1490,
            'currency'      => 'eur',
            'total_details' => (object) [
                'amount_discount' => 200,
                'amount_tax'      => 120,
            ],
            'discounts' => [],
        ];

        $this->service->confirmPayment($order, 'pi_x', 'cus_x', 'webhook', $checkoutSession);

        $order->refresh();
        $this->assertSame(1490, $order->total_cents);
        $this->assertSame(200, $order->discount_cents);
        $this->assertSame(120, $order->stripe_amount_tax);
        $this->assertSame('eur', $order->stripe_currency);
    }

    // ─── guard max_uses del cupón ─────────────────────────────────────────────

    public function test_confirm_payment_incrementa_times_used_del_cupon(): void
    {
        Bus::fake();
        Queue::fake();
        Mail::fake();

        $coupon = Coupon::factory()->create([
            'code'       => 'CUPONTEST',
            'max_uses'   => 1,
            'times_used' => 0,
        ]);
        $order = Order::factory()->create([
            'status'      => 'pending',
            'coupon_code' => 'CUPONTEST',
        ]);

        $this->service->confirmPayment($order, null, null, 'test');

        $this->assertSame(1, $coupon->fresh()->times_used);
    }

    public function test_confirm_payment_no_supera_max_uses_del_cupon(): void
    {
        Bus::fake();
        Queue::fake();
        Mail::fake();

        $coupon = Coupon::factory()->create([
            'code'       => 'MAXTEST',
            'max_uses'   => 1,
            'times_used' => 1,  // ya al límite
        ]);
        $order = Order::factory()->create([
            'status'      => 'pending',
            'coupon_code' => 'MAXTEST',
        ]);

        $this->service->confirmPayment($order, null, null, 'test');

        // El guard whereNull('max_uses')->orWhereColumn('times_used','<','max_uses')
        // evita que se incremente más allá de max_uses
        $this->assertSame(1, $coupon->fresh()->times_used);
    }

    // ─── despacho de jobs ─────────────────────────────────────────────────────

    public function test_confirm_payment_despacha_generate_formats_job_sin_upsells(): void
    {
        Bus::fake();
        Mail::fake();

        $product = Product::factory()->create(['product_key' => 'web_standard']);
        $order   = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'is_upsell'  => false,
        ]);

        $this->service->confirmPayment($order, null, null, 'test');

        // GenerateFormatsJob::dispatch() pasa por Bus, no por Queue directamente
        Bus::assertDispatched(GenerateFormatsJob::class);
    }

    public function test_confirm_payment_usa_bus_batch_con_items_upsell(): void
    {
        Bus::fake();
        Mail::fake();

        $product      = Product::factory()->create(['product_key' => 'web_standard']);
        $upsellProduct = Product::factory()->create(['product_key' => 'retrato_extra']);

        $order = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'is_upsell'  => false,
        ]);
        OrderItem::factory()->create([
            'order_id'      => $order->id,
            'product_id'    => $upsellProduct->id,
            'is_upsell'     => true,
            'generation_id' => null,
        ]);

        $this->service->confirmPayment($order, null, null, 'test');

        Bus::assertBatched(function ($batch) {
            return $batch->jobs[0] instanceof GenerateUpsellPortraitJob;
        });
    }
}
