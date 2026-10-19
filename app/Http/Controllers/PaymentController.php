<?php

namespace App\Http\Controllers;

use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderFulfillmentService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(GenerationSession $session)
    {
        $products = Product::active()->with('formats')->get();

        // Pre-rellenar desde query string (email de FreeDownloadMail, cupón)
        $prefillEmail = request()->query('email', '');
        $prefillCoupon = request()->query('coupon', '');
        $prefillProduct = request()->query('product', '');

        return view('checkout.index', compact('session', 'products', 'prefillEmail', 'prefillCoupon', 'prefillProduct'));
    }

    /**
     * Validar cupón via AJAX.
     */
    public function validateCoupon(Request $request, CouponService $couponService)
    {
        $request->validate([
            'code'        => ['required', 'string', 'max:50'],
            'email'       => ['required', 'email:rfc'],
            'product_key' => ['required', 'string'],
        ]);

        $result = $couponService->validate(
            $request->code,
            $request->email,
            $request->product_key,
        );

        if (!$result['valid']) {
            return response()->json([
                'valid' => false,
                'error' => __("ui.coupon_{$result['error']}"),
            ]);
        }

        $coupon = $result['coupon'];
        // Calcular descuento sobre el producto solicitado
        $product = Product::where('product_key', $request->product_key)->first();
        $discountCents = $coupon->discountCents($product?->priceCents() ?? 0);

        return response()->json([
            'valid'          => true,
            'code'           => $coupon->code,
            'discount_label' => $coupon->formattedValue(),
            'discount_cents' => $discountCents,
        ]);
    }

    public function createSession(Request $request)
    {
        $request->validate([
            'session_uuid'   => ['required', 'uuid', 'exists:generation_sessions,uuid'],
            'product_key'    => ['required', 'string', 'exists:products,product_key'],
            'email'          => ['required', 'email:rfc', 'max:255'],
            'extra_styles'   => ['nullable', 'array', 'max:5'],
            'extra_styles.*' => ['string', 'exists:artistic_styles,style_key'],
        ]);

        // Lock atómico sobre sesión+producto durante toda la creación del checkout.
        //
        // Sin él hay carrera: la idempotencia de más abajo busca una order pending
        // `whereNotNull('stripe_checkout_id')`, pero ese id se persiste DESPUÉS de
        // llamar a Stripe y fuera de la transacción. Dos POST concurrentes no se ven
        // el uno al otro y crean dos orders + dos Checkout Sessions.
        //
        // El lock debe abarcar la llamada a Stripe, así que no vale lockForUpdate:
        // la transacción libera al commit, antes de que exista el checkout_id.
        // block(10): la segunda petición espera a la primera y reutiliza su order,
        // que es exactamente el comportamiento idempotente que se busca.
        $lock = Cache::lock(
            "checkout:{$request->session_uuid}:{$request->product_key}",
            15
        );

        try {
            return $lock->block(10, fn () => $this->buildCheckoutSession($request));
        } catch (LockTimeoutException $e) {
            Log::warning('Checkout lock timeout', [
                'session' => $request->session_uuid,
                'product' => $request->product_key,
            ]);

            return back()->withErrors(['session' => __('ui.checkout_error')]);
        }
    }

    /**
     * Cuerpo real de createSession(). Siempre bajo el lock de arriba.
     */
    private function buildCheckoutSession(Request $request)
    {
        $session = GenerationSession::where('uuid', $request->session_uuid)->firstOrFail();

        // Rechazar sesiones expiradas o purgadas
        if ($session->purged_at) {
            return back()->withErrors(['session' => __('ui.session_expired')]);
        }
        if ($session->expires_at && $session->expires_at->isPast()) {
            return back()->withErrors(['session' => __('ui.session_expired')]);
        }

        $product = Product::where('product_key', $request->product_key)->firstOrFail();

        // Estilos extra (solo si el producto es pack_completo)
        $extraStyles = collect();
        $extraProduct = null;
        if ($request->extra_styles && $product->product_key === 'pack_completo') {
            $extraStyles = ArtisticStyle::active()
                ->whereIn('style_key', $request->extra_styles)
                ->where('id', '!=', $session->artistic_style_id)
                ->take(5)
                ->get();
            $extraProduct = Product::where('product_key', 'extra_style')->first();
        }

        $basePriceCents = $product->priceCents();
        $extraPriceCents = $extraStyles->count() * ($extraProduct?->priceCents() ?? 0);
        $subtotalCents = $basePriceCents + $extraPriceCents;
        $currency = 'eur';

        // Cupones se gestionan 100% en Stripe Checkout (allow_promotion_codes)
        $discountCents = 0;

        $totalCents = max(0, $subtotalCents - $discountCents);

        // Crear usuario + order dentro de transacción (con idempotencia)
        $order = DB::transaction(function () use ($request, $session, $product, $basePriceCents, $subtotalCents, $discountCents, $totalCents, $currency, $extraStyles, $extraProduct) {
            $user = User::firstOrCreate(
                ['email' => $request->email],
                ['ip_address' => $request->ip(), 'locale' => app()->getLocale()]
            );
            $user->update(['last_seen_at' => now()]);
            $session->update(['user_id' => $user->id, 'email_captured_at' => now()]);

            // Idempotencia: reutilizar la order pending SOLO si es realmente el mismo
            // intento de compra — misma sesión, mismo producto base y mismo importe.
            //
            // Antes filtraba solo por sesión (pese a lo que decía el comentario) y
            // exigía whereNotNull('stripe_checkout_id'). Dos consecuencias:
            //
            //  1. Cambiar de producto reutilizaba la order anterior: pedir el pack de
            //     9,99 € tras un intento de 1,00 € te mandaba al checkout de 1,00 €.
            //     En sentido inverso, cobraba 9,99 € a quien pidió 1,00 €.
            //  2. El checkout_id se persiste tras llamar a Stripe y fuera de la
            //     transacción, así que exigirlo hacía invisible una order recién
            //     creada y permitía duplicarla.
            //
            // total_cents cubre además el cambio de estilos extra sobre el mismo
            // pack: distinto importe = distinto intento = order nueva.
            $existingOrder = Order::where('generation_session_id', $session->id)
                ->where('status', 'pending')
                ->where('total_cents', $totalCents)
                ->whereHas('items', fn ($q) => $q
                    ->where('product_id', $product->id)
                    ->where('is_upsell', false))
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }

            $order = Order::create([
                'user_id'               => $user->id,
                'generation_session_id' => $session->id,
                'subtotal_cents'        => $subtotalCents,
                'discount_cents'        => $discountCents,
                'total_cents'           => $totalCents,
                'currency'              => $currency,
                'coupon_code'           => null, // Cupones gestionados por Stripe
                'status'                => 'pending',
            ]);

            // Item base (estilo original)
            $order->items()->create([
                'product_id'       => $product->id,
                'generation_id'    => $session->latestGeneration?->id,
                'quantity'         => 1,
                'unit_price_cents' => $basePriceCents,
                'total_cents'      => $basePriceCents,
            ]);

            // Items upsell (estilos extra)
            foreach ($extraStyles as $style) {
                $order->items()->create([
                    'product_id'        => $extraProduct->id,
                    'generation_id'     => null,
                    'quantity'          => 1,
                    'unit_price_cents'  => $extraProduct->priceCents(),
                    'total_cents'       => $extraProduct->priceCents(),
                    'is_upsell'         => true,
                    'artistic_style_id' => $style->id,
                    'generation_status' => 'pending',
                ]);
            }

            // Cupón: NO incrementar aquí. Se incrementa en el webhook tras pago confirmado.

            return $order;
        });

        // Si ya tiene checkout session de Stripe, redirigir a ella (idempotencia)
        if ($order->stripe_checkout_id && config('services.stripe.secret')) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $existingCs = $stripe->checkout->sessions->retrieve($order->stripe_checkout_id);
                if ($existingCs->status === 'open' && $existingCs->url) {
                    return redirect($existingCs->url);
                }
            } catch (\Exception $e) {
                // Checkout expirado o inválido, crear nuevo abajo
                $order->update(['stripe_checkout_id' => null]);
            }
        }

        // Simular pago solo si NO hay clave Stripe configurada
        if (!config('services.stripe.secret')) {
            app(OrderFulfillmentService::class)->confirmPayment($order, source: 'simulate');
            return redirect()->route(locale_route_name('checkout.success'), ['order' => $order->uuid]);
        }

        // Stripe Checkout (producción / sandbox)
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            // Crear o reutilizar Customer de Stripe (necesario para automatic_tax)
            $user = $order->session->user;
            if ($user->stripe_customer_id) {
                try {
                    $stripe->customers->retrieve($user->stripe_customer_id);
                } catch (\Exception $e) {
                    // Customer eliminado en Stripe, recrear
                    $user->update(['stripe_customer_id' => null]);
                }
            }
            if (!$user->stripe_customer_id) {
                // País estimado desde locale para que Stripe Tax calcule IVA
                $countryCode = match(app()->getLocale()) {
                    'en' => 'US',
                    'fr' => 'FR',
                    'pt' => 'PT',
                    'it' => 'IT',
                    default => 'ES',
                };
                $stripeCustomer = $stripe->customers->create([
                    'email' => $user->email,
                    'address' => ['country' => $countryCode],
                    'metadata' => ['genpic_user_id' => $user->id],
                ]);
                $user->update(['stripe_customer_id' => $stripeCustomer->id]);
            }

            $locale = app()->getLocale();
            $productName = $product->t('name');
            $productDesc = $product->t('description');

            // Imagen del retrato para mostrar en Stripe Checkout
            $previewImages = [];
            $generation = $session->latestGeneration;
            if ($generation && $generation->watermarked_image_path) {
                $previewImages[] = route('secure.watermarked', $session);
            }

            $lineItems = [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'        => $productName . ' — GenPic',
                        'description' => $productDesc,
                        'images'      => $previewImages,
                        'tax_code'    => 'txcd_10201000', // Electronically Supplied Services
                    ],
                    'unit_amount'  => $basePriceCents,
                    'tax_behavior' => 'inclusive',
                ],
                'quantity' => 1,
            ]];

            // Línea adicional para estilos extra
            if ($extraStyles->isNotEmpty() && $extraProduct) {
                $extraName = $extraProduct->t('name');
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => [
                            'name'     => $extraName . ' — GenPic',
                            'tax_code' => 'txcd_10201000', // Electronically Supplied Services
                        ],
                        'unit_amount'  => $extraProduct->priceCents(),
                        'tax_behavior' => 'inclusive',
                    ],
                    'quantity' => $extraStyles->count(),
                ];
            }

            $checkoutParams = [
                'line_items' => $lineItems,
                'automatic_tax' => ['enabled' => true],
                'mode'           => 'payment',
                'locale'         => config("genpic.locale_map.{$locale}.stripe", $locale),
                'success_url'    => locale_route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'     => locale_route('checkout.cancel') . '?order=' . $order->uuid,
                'metadata'       => ['order_uuid' => $order->uuid, 'session_uuid' => $session->uuid],
                'customer'       => $user->stripe_customer_id,
                'customer_update' => [
                    'address' => 'auto',
                    'name'    => 'auto',
                ],
                'billing_address_collection' => 'required',
                'tax_id_collection' => ['enabled' => true, 'required' => 'never'],
                'invoice_creation' => [
                    'enabled' => true,
                    'invoice_data' => [
                        'description' => "GenPic — {$productName}",
                    ],
                ],
            ];

            // Cupones gestionados 100% por Stripe Checkout
            $checkoutParams['allow_promotion_codes'] = true;

            $checkoutSession = $stripe->checkout->sessions->create($checkoutParams);

            $order->update(['stripe_checkout_id' => $checkoutSession->id]);

            return redirect($checkoutSession->url);
        } catch (\Exception $e) {
            Log::error('Stripe checkout failed', ['order' => $order->uuid, 'error' => $e->getMessage()]);

            $order->update(['status' => 'failed']);

            return back()->withErrors(['stripe' => __('ui.checkout_stripe_error')]);
        }
    }

    public function success(Request $request)
    {
        $order = null;

        // Test mode: order UUID directo
        if ($request->has('order')) {
            $order = Order::where('uuid', $request->query('order'))->first();
        }
        // Stripe: buscar por checkout session ID
        elseif ($request->has('session_id')) {
            $order = Order::where('stripe_checkout_id', $request->query('session_id'))->first();
        }

        if (!$order) {
            return redirect()->route(locale_route_name('landing'))
                ->with('error', __('ui.checkout_order_not_found'));
        }

        return view('checkout.success', compact('order'));
    }

    public function cancel(Request $request)
    {
        $order = Order::where('uuid', $request->query('order'))->first();
        return view('checkout.cancel', compact('order'));
    }

}
