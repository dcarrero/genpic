<?php

namespace Tests\Feature;

use App\Http\Middleware\GenerationRateLimit;
use App\Jobs\GenerateFormatsJob;
use App\Jobs\GeneratePortraitJob;
use App\Mail\DownloadReadyMail;
use App\Models\ArtisticStyle;
use App\Models\Format;
use App\Models\Generation;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * E2E test del flujo completo: upload → checkout → webhook Stripe → formatos → descarga.
 *
 * Simula el recorrido completo del usuario con Stripe en modo test.
 * No hace llamadas reales a Stripe API — verifica la lógica interna.
 */
class StripeE2ETest extends TestCase
{
    use RefreshDatabase;

    private ArtisticStyle $style;
    private Product $product;
    private Format $format;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->style = ArtisticStyle::factory()->create([
            'style_key'   => 'renacimiento',
            'label_es'    => 'Renacimiento Clásico',
            'base_prompt' => 'A classical renaissance portrait',
        ]);

        $this->format = Format::factory()->create([
            'format_key' => 'web_basic',
            'label_es'   => 'Calidad Web',
        ]);

        $this->product = Product::factory()->create([
            'product_key'    => 'web_quality',
            'name_es'        => 'Retrato Digital — Web',
            'price_es_cents' => 499,
        ]);

        $this->product->formats()->attach($this->format);
    }

    // ---------------------------------------------------------------
    // FLUJO COMPLETO SIN STRIPE (modo simulado)
    // ---------------------------------------------------------------

    public function test_full_funnel_without_stripe_key(): void
    {
        Queue::fake();
        Mail::fake();
        config(['services.stripe.secret' => null]);
        $this->withoutMiddleware(GenerationRateLimit::class);

        // 1. Upload foto
        $uploadResponse = $this->postJson('/create/upload', [
            'photo'     => UploadedFile::fake()->image('family.jpg', 1024, 768),
            'style_key' => 'renacimiento',
        ]);
        $uploadResponse->assertStatus(200);
        $sessionUuid = $uploadResponse->json('session_uuid');
        $this->assertNotNull($sessionUuid);

        Queue::assertPushed(GeneratePortraitJob::class);

        // 2. Simular que la generación terminó
        $session = GenerationSession::where('uuid', $sessionUuid)->first();
        $session->update(['status' => 'watermarked']);

        $generation = Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
            'artistic_style_id'    => $this->style->id,
        ]);

        // 3. Checkout sin Stripe → simula pago directo
        $checkoutResponse = $this->post('/checkout/session', [
            'session_uuid' => $sessionUuid,
            'product_key'  => 'web_quality',
            'email'        => 'comprador@gmail.com',
        ]);
        $checkoutResponse->assertRedirect();

        // 4. Verificar que se creó order pagada
        $order = Order::where('generation_session_id', $session->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(499, $order->total_cents);
        $this->assertEquals('eur', $order->currency);
        $this->assertNotNull($order->paid_at);

        // 5. Verificar user creado
        $this->assertDatabaseHas('users', ['email' => 'comprador@gmail.com']);

        // 6. Verificar order_items
        $this->assertDatabaseHas('order_items', [
            'order_id'        => $order->id,
            'product_id'      => $this->product->id,
            'unit_price_cents' => 499,
        ]);

        // 7. GenerateFormatsJob fue despachado
        Queue::assertPushed(GenerateFormatsJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });

        // 8. Session marcada como paid
        $session->refresh();
        $this->assertEquals('paid', $session->status);
    }

    // ---------------------------------------------------------------
    // WEBHOOK STRIPE — checkout.session.completed
    // ---------------------------------------------------------------

    public function test_stripe_webhook_marks_order_paid_and_dispatches_formats(): void
    {
        Queue::fake();

        $session = GenerationSession::factory()->watermarked()->create();
        Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);

        $stripeCheckoutId = 'cs_test_' . bin2hex(random_bytes(16));

        $order = Order::factory()->create([
            'user_id'               => $session->user_id,
            'generation_session_id' => $session->id,
            'stripe_checkout_id'    => $stripeCheckoutId,
            'total_cents'           => 499,
            'status'                => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
        ]);

        // Construir payload Stripe realista
        $stripePayload = $this->buildCheckoutCompletedPayload(
            checkoutId: $stripeCheckoutId,
            paymentIntentId: 'pi_test_' . bin2hex(random_bytes(16)),
            customerId: 'cus_test_' . bin2hex(random_bytes(16)),
            amountTotal: 499,
            currency: 'eur',
            email: $session->user->email,
        );

        // Firmar el webhook con un secret de test
        $webhookSecret = 'whsec_test_secret_key_for_testing';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $timestamp = time();
        $signedPayload = "{$timestamp}.{$stripePayload}";
        $signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
        $sigHeader = "t={$timestamp},v1={$signature}";

        $response = $this->postJson('/webhooks/stripe', [], [
            'Stripe-Signature' => $sigHeader,
            'Content-Type' => 'application/json',
        ]);

        // Stripe\Webhook::constructEvent usa el payload crudo del body,
        // no el parsed JSON. Hay que enviar como raw body.
        // Necesitamos usar call() directamente para enviar raw body.
        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            server: [
                'HTTP_STRIPE_SIGNATURE' => $sigHeader,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $stripePayload,
        );

        $response->assertStatus(200);

        // Verificar que la order se actualizó
        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->stripe_payment_intent_id);
        $this->assertNotNull($order->stripe_customer_id);

        // Verificar session actualizada
        $session->refresh();
        $this->assertEquals('paid', $session->status);

        // GenerateFormatsJob despachado
        Queue::assertPushed(GenerateFormatsJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    public function test_stripe_webhook_idempotency_ignores_duplicate(): void
    {
        Queue::fake();

        $stripeCheckoutId = 'cs_test_duplicate_' . bin2hex(random_bytes(8));

        $session = GenerationSession::factory()->paid()->create();
        $order = Order::factory()->paid()->create([
            'user_id'               => $session->user_id,
            'generation_session_id' => $session->id,
            'stripe_checkout_id'    => $stripeCheckoutId,
        ]);

        $stripePayload = $this->buildCheckoutCompletedPayload(
            checkoutId: $stripeCheckoutId,
            paymentIntentId: 'pi_test_dup',
            customerId: 'cus_test_dup',
        );

        $webhookSecret = 'whsec_test_secret_key_for_testing';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$stripePayload}", $webhookSecret);

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            server: [
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $stripePayload,
        );

        $response->assertStatus(200);

        // No debe despachar GenerateFormatsJob de nuevo
        Queue::assertNotPushed(GenerateFormatsJob::class);
    }

    public function test_stripe_webhook_invalid_signature_returns_400(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_real_secret']);

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            server: [
                'HTTP_STRIPE_SIGNATURE' => 't=123,v1=invalid_hash',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: '{"type": "checkout.session.completed"}',
        );

        $response->assertStatus(400);
    }

    public function test_stripe_webhook_unknown_checkout_id_does_not_crash(): void
    {
        $stripePayload = $this->buildCheckoutCompletedPayload(
            checkoutId: 'cs_test_nonexistent_order',
            paymentIntentId: 'pi_test_ghost',
            customerId: 'cus_test_ghost',
        );

        $webhookSecret = 'whsec_test_key';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$stripePayload}", $webhookSecret);

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            server: [
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $stripePayload,
        );

        // No debe crashear — retorna 200 aunque no encuentre el order
        $response->assertStatus(200);
    }

    public function test_stripe_webhook_ignores_non_checkout_events(): void
    {
        $payload = json_encode([
            'id'   => 'evt_test_' . bin2hex(random_bytes(8)),
            'type' => 'payment_intent.created',
            'data' => ['object' => ['id' => 'pi_test_irrelevant']],
        ]);

        $webhookSecret = 'whsec_test_key';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            server: [
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $payload,
        );

        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // CHECKOUT → ORDER CREATION
    // ---------------------------------------------------------------

    public function test_checkout_creates_order_with_correct_amounts(): void
    {
        Queue::fake();
        config(['services.stripe.secret' => null]);

        $session = GenerationSession::factory()->watermarked()->create();
        Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);

        $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key'  => 'web_quality',
            'email'        => 'test@gmail.com',
        ]);

        $order = Order::where('generation_session_id', $session->id)->first();

        $this->assertEquals(499, $order->subtotal_cents);
        $this->assertEquals(499, $order->total_cents);
        $this->assertEquals('eur', $order->currency);
        $this->assertEquals(0, $order->discount_cents);
    }

    public function test_checkout_links_user_and_session(): void
    {
        Queue::fake();
        config(['services.stripe.secret' => null]);

        $session = GenerationSession::factory()->withoutUser()->create();
        Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);

        $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key'  => 'web_quality',
            'email'        => 'nuevo@gmail.com',
        ]);

        $user = User::where('email', 'nuevo@gmail.com')->first();
        $this->assertNotNull($user);

        $session->refresh();
        $this->assertEquals($user->id, $session->user_id);
        $this->assertNotNull($session->email_captured_at);
    }

    public function test_checkout_reuses_existing_user(): void
    {
        Queue::fake();
        config(['services.stripe.secret' => null]);

        $existingUser = User::factory()->create(['email' => 'repeat@gmail.com']);
        $session = GenerationSession::factory()->create(['user_id' => $existingUser->id]);
        Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);

        $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key'  => 'web_quality',
            'email'        => 'repeat@gmail.com',
        ]);

        // Solo debe existir 1 user con ese email
        $this->assertEquals(1, User::where('email', 'repeat@gmail.com')->count());

        $order = Order::where('generation_session_id', $session->id)->first();
        $this->assertEquals($existingUser->id, $order->user_id);
    }

    // ---------------------------------------------------------------
    // POST-PAGO: DESCARGAS
    // ---------------------------------------------------------------

    public function test_download_flow_after_payment(): void
    {
        $session = GenerationSession::factory()->paid()->create();
        $generation = Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);

        $order = Order::factory()->ready()->create([
            'user_id'               => $session->user_id,
            'generation_session_id' => $session->id,
        ]);

        $exportPath = "exports/{$session->uuid}/web_basic_test123.jpg";
        Storage::disk('local')->put($exportPath, 'exported image data');

        OrderItem::factory()->create([
            'order_id'      => $order->id,
            'generation_id' => $generation->id,
            'export_paths'  => ['web_basic' => $exportPath],
            'exports_ready' => true,
        ]);

        // Descargas requieren signed URL desde v1.3: la firma garantiza que el link
        // vino del email de descarga y no es un acceso directo sin autorización.
        $url = URL::signedRoute('secure.export', [
            'order'  => $order->uuid,
            'format' => 'web_basic',
        ]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContains('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_download_blocked_for_unpaid_order(): void
    {
        $session = GenerationSession::factory()->create();
        $order = Order::factory()->create([
            'user_id'               => $session->user_id,
            'generation_session_id' => $session->id,
            'status'                => 'pending',
        ]);

        // Signed URL válida pero order sin pagar → 403 del controlador.
        $url = URL::signedRoute('secure.export', [
            'order'  => $order->uuid,
            'format' => 'web_basic',
        ]);
        $response = $this->get($url);
        $response->assertStatus(403);
    }

    public function test_download_blocked_after_purge(): void
    {
        $session = GenerationSession::factory()->paid()->create(['purged_at' => now()]);
        $order = Order::factory()->paid()->create([
            'user_id'               => $session->user_id,
            'generation_session_id' => $session->id,
        ]);

        // Signed URL válida pero sesión purgada → 410 del controlador.
        $url = URL::signedRoute('secure.export', [
            'order'  => $order->uuid,
            'format' => 'web_basic',
        ]);
        $response = $this->get($url);
        $response->assertStatus(410);
    }

    // ---------------------------------------------------------------
    // SUCCESS / CANCEL PAGES
    // ---------------------------------------------------------------

    public function test_success_page_shows_order_by_uuid(): void
    {
        $order = Order::factory()->paid()->create();

        $response = $this->get('/checkout/success?order=' . $order->uuid);
        $response->assertStatus(200);
    }

    public function test_success_page_shows_order_by_stripe_session_id(): void
    {
        $order = Order::factory()->paid()->withStripe()->create();

        $response = $this->get('/checkout/success?session_id=' . $order->stripe_checkout_id);
        $response->assertStatus(200);
    }

    public function test_cancel_page_shows_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->get('/checkout/cancel?order=' . $order->uuid);
        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // EDGE CASES
    // ---------------------------------------------------------------

    public function test_multiple_orders_same_session(): void
    {
        Queue::fake();
        config(['services.stripe.secret' => null]);

        $session = GenerationSession::factory()->watermarked()->create();
        Generation::factory()->watermarked()->create([
            'generation_session_id' => $session->id,
        ]);

        // Primera compra
        $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key'  => 'web_quality',
            'email'        => 'multi@gmail.com',
        ]);

        // Segunda compra (ej: upgrade)
        $session->update(['status' => 'watermarked']); // Reset para permitir nueva order
        $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key'  => 'web_quality',
            'email'        => 'multi@gmail.com',
        ]);

        $orders = Order::where('generation_session_id', $session->id)->get();
        $this->assertGreaterThanOrEqual(2, $orders->count());
    }

    public function test_checkout_with_invalid_product_fails(): void
    {
        $session = GenerationSession::factory()->create();

        $response = $this->post('/checkout/session', [
            'session_uuid' => $session->uuid,
            'product_key'  => 'nonexistent_product',
            'email'        => 'test@gmail.com',
        ]);

        $response->assertSessionHasErrors('product_key');
    }

    public function test_checkout_with_invalid_session_fails(): void
    {
        $response = $this->postJson('/checkout/session', [
            'session_uuid' => '00000000-0000-0000-0000-000000000000',
            'product_key'  => 'web_quality',
            'email'        => 'test@gmail.com',
        ]);

        $response->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------------

    /**
     * Construye un payload JSON de evento Stripe checkout.session.completed.
     * Replica la estructura real de Stripe.
     */
    private function buildCheckoutCompletedPayload(
        string $checkoutId,
        string $paymentIntentId,
        string $customerId,
        int $amountTotal = 499,
        string $currency = 'eur',
        string $email = 'test@example.com',
    ): string {
        return json_encode([
            'id'               => 'evt_test_' . bin2hex(random_bytes(8)),
            'object'           => 'event',
            'api_version'      => '2024-06-20',
            'created'          => time(),
            'type'             => 'checkout.session.completed',
            'livemode'         => false,
            'pending_webhooks' => 1,
            'data'             => [
                'object' => [
                    'id'                   => $checkoutId,
                    'object'               => 'checkout.session',
                    'amount_total'         => $amountTotal,
                    'currency'             => $currency,
                    'customer'             => $customerId,
                    'customer_email'       => $email,
                    'mode'                 => 'payment',
                    'payment_intent'       => $paymentIntentId,
                    'payment_status'       => 'paid',
                    'status'               => 'complete',
                    'success_url'          => config('app.url') . '/checkout/success',
                    'cancel_url'           => config('app.url') . '/checkout/cancel',
                    'livemode'             => false,
                    'metadata'             => [],
                    'automatic_tax'        => ['enabled' => true, 'status' => 'complete'],
                    'invoice'              => 'in_test_' . bin2hex(random_bytes(8)),
                    'total_details'        => [
                        'amount_discount' => 0,
                        'amount_shipping' => 0,
                        'amount_tax'      => 0,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Custom assert para str_contains (PHPUnit no tiene uno built-in).
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
