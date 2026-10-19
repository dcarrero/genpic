<?php

namespace App\Console\Commands;

use App\Models\ArtisticStyle;
use App\Models\Generation;
use App\Models\GenerationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Comando para probar el flujo Stripe E2E con tarjeta de test.
 *
 * Crea una sesión con datos fake y genera un Stripe Checkout URL
 * que se puede abrir en el navegador para pagar con tarjeta de test.
 *
 * Tarjetas de test Stripe:
 *   - 4242 4242 4242 4242 → Pago exitoso
 *   - 4000 0000 0000 3220 → Requiere 3D Secure
 *   - 4000 0000 0000 9995 → Pago rechazado
 */
class StripeTestCheckout extends Command
{
    protected $signature = 'stripe:test-checkout
        {--product= : product_key del producto (default: primer producto activo)}
        {--email=test@genpic.com : Email del comprador}';

    protected $description = 'Crea un Stripe Checkout de prueba para verificar el flujo E2E';

    public function handle(StorageService $storage): int
    {
        $stripeSecret = config('services.stripe.secret');
        if (!$stripeSecret) {
            $this->error('STRIPE_SECRET no está configurado en .env');
            $this->line('Configura STRIPE_SECRET con tu clave de test (sk_test_...)');
            return self::FAILURE;
        }

        if (!str_starts_with($stripeSecret, 'sk_test_')) {
            $this->error('¡PELIGRO! La clave Stripe NO es de test. Abortando.');
            return self::FAILURE;
        }

        $webhookSecret = config('services.stripe.webhook_secret');
        if (!$webhookSecret) {
            $this->warn('STRIPE_WEBHOOK_SECRET no configurado. El webhook no funcionará.');
            $this->line('Usa: stripe listen --forward-to localhost:8000/webhooks/stripe');
        }

        // Producto
        $productKey = $this->option('product');
        $product = $productKey
            ? Product::where('product_key', $productKey)->first()
            : Product::active()->first();

        if (!$product) {
            $this->error('No hay productos activos. Ejecuta: php artisan db:seed');
            return self::FAILURE;
        }

        // Estilo
        $style = ArtisticStyle::active()->first();
        if (!$style) {
            $this->error('No hay estilos activos. Ejecuta: php artisan db:seed');
            return self::FAILURE;
        }

        $email = $this->option('email');

        // Crear datos de test
        $user = User::firstOrCreate(['email' => $email], [
            'locale' => 'es',
            'ip_address' => '127.0.0.1',
        ]);

        // Crear imagen de placeholder
        $fakePath = $storage->store(
            $this->createTestImage(),
            'originals',
            'stripe-test',
            'jpg'
        );

        $session = GenerationSession::create([
            'user_id'             => $user->id,
            'style_key'           => $style->style_key,
            'artistic_style_id'   => $style->id,
            'original_photo_path' => $fakePath,
            'original_filename'   => 'test-photo.jpg',
            'original_size_bytes' => 1024,
            'face_detected'       => true,
            'status'              => 'watermarked',
            'ip_address'          => '127.0.0.1',
            'locale'              => 'es',
            'email_captured_at'   => now(),
        ]);

        // Crear generación fake
        $genPath = $storage->store(
            $this->createTestImage(),
            'generations',
            $session->uuid,
            'png'
        );

        $generation = Generation::create([
            'generation_session_id' => $session->id,
            'artistic_style_id'     => $style->id,
            'status'                => 'watermarked',
            'provider'              => 'test',
            'raw_image_path'        => $genPath,
            'watermarked_image_path' => $genPath,
            'prompt_used'           => 'Test prompt',
            'attempt_number'        => 1,
        ]);

        $this->info('Datos de test creados:');
        $this->table(['Campo', 'Valor'], [
            ['Session UUID', $session->uuid],
            ['User email', $email],
            ['Producto', "{$product->name_es} ({$product->product_key})"],
            ['Precio', $product->formattedPrice()],
            ['Estilo', $style->label_es],
        ]);

        // Crear Stripe Checkout
        $this->line('');
        $this->info('Creando Stripe Checkout Session...');

        try {
            $stripe = new \Stripe\StripeClient($stripeSecret);

            $locale = 'es';
            $checkoutSession = $stripe->checkout->sessions->create([
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => [
                            'name'        => $product->name_es . ' — GenPic (TEST)',
                            'description' => $product->description_es,
                            'tax_code'    => 'txcd_10502000',
                        ],
                        'unit_amount'  => $product->priceCents(),
                        'tax_behavior' => 'inclusive',
                    ],
                    'quantity' => 1,
                ]],
                'automatic_tax'             => ['enabled' => true],
                'mode'                      => 'payment',
                'locale'                    => 'es',
                'success_url'               => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'                => route('checkout.cancel') . '?order=test',
                'metadata'                  => ['session_uuid' => $session->uuid, 'test' => true],
                'customer_email'            => $email,
                'billing_address_collection'=> 'required',
                'tax_id_collection'         => ['enabled' => true],
                'invoice_creation'          => [
                    'enabled' => true,
                    'invoice_data' => ['description' => 'GenPic — Test checkout'],
                ],
            ]);

            // Crear order vinculada
            $order = $session->orders()->create([
                'user_id'            => $user->id,
                'stripe_checkout_id' => $checkoutSession->id,
                'subtotal_cents'     => $product->priceCents(),
                'total_cents'        => $product->priceCents(),
                'currency'           => 'eur',
                'status'             => 'pending',
            ]);

            $order->items()->create([
                'product_id'       => $product->id,
                'generation_id'    => $generation->id,
                'quantity'         => 1,
                'unit_price_cents' => $product->priceCents(),
                'total_cents'      => $product->priceCents(),
            ]);

            $this->newLine();
            $this->info('Stripe Checkout creado:');
            $this->line("  Checkout ID: {$checkoutSession->id}");
            $this->line("  Order UUID:  {$order->uuid}");
            $this->newLine();
            $this->warn('Abre esta URL en tu navegador:');
            $this->line($checkoutSession->url);
            $this->newLine();
            $this->line('Tarjetas de test:');
            $this->table(['Tarjeta', 'Resultado'], [
                ['4242 4242 4242 4242', 'Pago exitoso'],
                ['4000 0000 0000 3220', 'Requiere 3D Secure'],
                ['4000 0000 0000 9995', 'Pago rechazado'],
            ]);
            $this->newLine();
            $this->line('Para recibir el webhook localmente:');
            $this->line('  stripe listen --forward-to ' . config('app.url') . '/webhooks/stripe');

        } catch (\Exception $e) {
            $this->error('Error Stripe: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function createTestImage(): string
    {
        // Crear imagen PNG 100x100 de color sólido
        $img = imagecreatetruecolor(100, 100);
        $color = imagecolorallocate($img, 100, 50, 80);
        imagefill($img, 0, 0, $color);
        ob_start();
        imagejpeg($img, null, 80);
        $data = ob_get_clean();
        imagedestroy($img);
        return $data;
    }
}
