<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookSignedTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_secret_005';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => $this->webhookSecret]);
        Bus::fake();
        Mail::fake();
    }

    /**
     * Construye y envía un POST con firma Stripe válida.
     */
    private function signedPost(array $event): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode($event);
        $ts      = time();
        $sig     = hash_hmac('sha256', "{$ts}.{$payload}", $this->webhookSecret);
        $header  = "t={$ts},v1={$sig}";

        return $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $header,
                'CONTENT_TYPE'          => 'application/json',
            ],
            $payload
        );
    }

    /**
     * Payload mínimo para checkout.session.completed.
     */
    private function checkoutCompletedEvent(
        string $checkoutId,
        string $paymentStatus = 'paid',
        array $overrides = []
    ): array {
        $session = array_merge([
            'object'         => 'checkout.session',
            'id'             => $checkoutId,
            'payment_status' => $paymentStatus,
            'payment_intent' => 'pi_test_' . $checkoutId,
            'customer'       => 'cus_test_' . $checkoutId,
            'amount_total'   => 990,
            'currency'       => 'eur',
            'total_details'  => [
                'amount_discount' => 0,
                'amount_tax'      => 0,
            ],
            'discounts' => [],
        ], $overrides);

        return [
            'id'      => 'evt_' . $checkoutId,
            'object'  => 'event',
            'type'    => 'checkout.session.completed',
            'created' => time(),
            'data'    => ['object' => $session],
            'livemode' => false,
        ];
    }

    /**
     * Payload para async_payment_succeeded.
     */
    private function asyncPaymentSucceededEvent(string $checkoutId): array
    {
        $session = [
            'object'         => 'checkout.session',
            'id'             => $checkoutId,
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_' . $checkoutId,
            'customer'       => 'cus_test_' . $checkoutId,
            'amount_total'   => 990,
            'currency'       => 'eur',
            'total_details'  => [
                'amount_discount' => 0,
                'amount_tax'      => 0,
            ],
            'discounts' => [],
        ];

        return [
            'id'      => 'evt_async_' . $checkoutId,
            'object'  => 'event',
            'type'    => 'checkout.session.async_payment_succeeded',
            'created' => time(),
            'data'    => ['object' => $session],
            'livemode' => false,
        ];
    }

    // ─── 1. Firma inválida → 400 ─────────────────────────────────────────────

    public function test_firma_invalida_devuelve_400(): void
    {
        $payload = json_encode(['type' => 'checkout.session.completed']);

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => 't=999,v1=firma_invalida',
                'CONTENT_TYPE'          => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(400);
    }

    // ─── 2. completed con payment_status paid → order paid ──────────────────

    public function test_completed_con_payment_paid_procesa_order(): void
    {
        $checkoutId = 'cs_test_paid_' . uniqid();
        $order = Order::factory()->create([
            'status'             => 'pending',
            'stripe_checkout_id' => $checkoutId,
        ]);

        $response = $this->signedPost($this->checkoutCompletedEvent($checkoutId, 'paid'));

        $response->assertStatus(200);
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
    }

    // ─── 3. completed con payment_status unpaid → order sigue pending ────────

    public function test_completed_con_payment_unpaid_no_procesa_order(): void
    {
        $checkoutId = 'cs_test_unpaid_' . uniqid();
        $order = Order::factory()->create([
            'status'             => 'pending',
            'stripe_checkout_id' => $checkoutId,
        ]);

        $response = $this->signedPost($this->checkoutCompletedEvent($checkoutId, 'unpaid'));

        $response->assertStatus(200);
        $order->refresh();
        // El pago es async — no debe procesarse todavía
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->paid_at);
    }

    // ─── 4. async_payment_succeeded → order paid ────────────────────────────

    public function test_async_payment_succeeded_procesa_order(): void
    {
        $checkoutId = 'cs_test_async_' . uniqid();
        $order = Order::factory()->create([
            'status'             => 'pending',
            'stripe_checkout_id' => $checkoutId,
        ]);

        // Primero llega completed con unpaid (SEPA, etc.)
        $this->signedPost($this->checkoutCompletedEvent($checkoutId, 'unpaid'));
        $this->assertSame('pending', $order->fresh()->status);

        // Luego llega async_payment_succeeded
        $response = $this->signedPost($this->asyncPaymentSucceededEvent($checkoutId));

        $response->assertStatus(200);
        $this->assertSame('paid', $order->fresh()->status);
    }

    // ─── 5. Webhook duplicado → idempotente ──────────────────────────────────

    public function test_webhook_duplicado_no_reprocesa(): void
    {
        $checkoutId = 'cs_test_dup_' . uniqid();
        $coupon = \App\Models\Coupon::factory()->create([
            'code'       => 'DUPTEST',
            'max_uses'   => null,
            'times_used' => 0,
        ]);
        $order = Order::factory()->create([
            'status'             => 'pending',
            'stripe_checkout_id' => $checkoutId,
            'coupon_code'        => 'DUPTEST',
        ]);

        // Primer webhook
        $this->signedPost($this->checkoutCompletedEvent($checkoutId, 'paid'));
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, $coupon->fresh()->times_used);

        // Segundo webhook idéntico
        $this->signedPost($this->checkoutCompletedEvent($checkoutId, 'paid'));

        // El cupón no se incrementa de nuevo
        $this->assertSame(1, $coupon->fresh()->times_used);
        // La order sigue paid (no retrocede)
        $this->assertSame('paid', $order->fresh()->status);
    }

    // ─── 6. checkout_id desconocido → 200 sin tocar nada ────────────────────

    public function test_checkout_id_desconocido_devuelve_200_sin_cambios(): void
    {
        $existingOrder = Order::factory()->create(['status' => 'pending']);

        $response = $this->signedPost(
            $this->checkoutCompletedEvent('cs_test_nonexistent_xyz123', 'paid')
        );

        $response->assertStatus(200);
        // La order existente no se toca
        $this->assertSame('pending', $existingOrder->fresh()->status);
    }
}
