<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CouponService
{
    /**
     * Crear cupón manual (admin) — lo crea en Stripe + local.
     */
    public function create(
        string $code,
        string $type,
        int $value,
        ?array $productKeys = null,
        ?int $maxUses = null,
        bool $oncePerEmail = true,
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validUntil = null,
    ): Coupon {
        $stripeData = $this->createInStripe($code, $type, $value, $validUntil);

        return Coupon::create([
            'code'                       => strtoupper($code),
            'stripe_coupon_id'           => $stripeData['coupon_id'],
            'stripe_promotion_code_id'   => $stripeData['promotion_code_id'],
            'type'                       => $type,
            'value'                      => $value,
            'product_keys'               => $productKeys,
            'max_uses'                   => $maxUses,
            'once_per_email'             => $oncePerEmail,
            'valid_from'                 => $validFrom,
            'valid_until'                => $validUntil,
            'auto_generated'             => false,
            'active'                     => true,
        ]);
    }

    /**
     * Crear cupón automático para emails de abandono.
     * Código único, 1 uso, válido hasta expiración de sesión.
     */
    public function createAbandonmentCoupon(
        string $email,
        int $discountPercent,
        ?string $productKey = null,
        ?\DateTimeInterface $expiresAt = null,
    ): Coupon {
        $code = 'AUTO-' . strtoupper(Str::random(8));

        $stripeData = $this->createInStripe($code, 'percent', $discountPercent, $expiresAt);

        return Coupon::create([
            'code'                       => $code,
            'stripe_coupon_id'           => $stripeData['coupon_id'],
            'stripe_promotion_code_id'   => $stripeData['promotion_code_id'],
            'type'                       => 'percent',
            'value'                      => $discountPercent,
            'product_keys'               => $productKey ? [$productKey] : null,
            'max_uses'                   => 1,
            'once_per_email'             => true,
            'valid_until'                => $expiresAt,
            'auto_generated'             => true,
            'active'                     => true,
        ]);
    }

    /**
     * Validar un cupón. Devuelve array con 'valid' y 'error' si falla.
     */
    public function validate(string $code, string $email, string $productKey): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return ['valid' => false, 'error' => 'coupon_not_found'];
        }

        if (!$coupon->active) {
            return ['valid' => false, 'error' => 'coupon_inactive'];
        }

        if ($coupon->isExpired()) {
            return ['valid' => false, 'error' => 'coupon_expired'];
        }

        if ($coupon->isExhausted()) {
            return ['valid' => false, 'error' => 'coupon_exhausted'];
        }

        if (!$coupon->appliesToProduct($productKey)) {
            return ['valid' => false, 'error' => 'coupon_wrong_product'];
        }

        if ($coupon->once_per_email) {
            $alreadyUsed = Order::where('coupon_code', $coupon->code)
                ->whereHas('user', fn ($q) => $q->where('email', $email))
                ->whereIn('status', ['paid', 'ready', 'processing'])
                ->exists();

            if ($alreadyUsed) {
                return ['valid' => false, 'error' => 'coupon_already_used'];
            }
        }

        return ['valid' => true, 'coupon' => $coupon];
    }

    /**
     * Aplicar cupón a una order (actualiza descuento y total).
     */
    public function apply(Order $order, Coupon $coupon): void
    {
        $discount = $coupon->discountCents($order->subtotal_cents);

        $order->update([
            'coupon_code'    => $coupon->code,
            'discount_cents' => $discount,
            'total_cents'    => max(0, $order->subtotal_cents - $discount),
        ]);

        $coupon->increment('times_used');
    }

    /**
     * Sincronizar cupón existente con Stripe (para crear desde admin).
     */
    public function syncToStripe(Coupon $coupon): array
    {
        return $this->createInStripe(
            $coupon->code, $coupon->type, $coupon->value, $coupon->valid_until,
            $coupon->first_time_only ?? false, $coupon->minimum_amount_cents
        );
    }

    /**
     * Crear cupón + promotion code en Stripe.
     */
    private function createInStripe(
        string $code,
        string $type,
        int $value,
        ?\DateTimeInterface $expiresAt = null,
        bool $firstTimeOnly = false,
        ?int $minimumAmountCents = null,
    ): array {
        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            Log::info('Stripe no configurado — cupón solo local', ['code' => $code]);
            return ['coupon_id' => null, 'promotion_code_id' => null];
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            // Buscar promotion code existente para evitar duplicados
            $existingPromos = \Stripe\PromotionCode::all(['code' => $code, 'limit' => 1]);
            if ($existingPromos->data) {
                $promo = $existingPromos->data[0];
                return [
                    'coupon_id'         => $promo->coupon ?? $promo->promotion->coupon ?? null,
                    'promotion_code_id' => $promo->id,
                ];
            }

            // Crear cupón
            $couponParams = ['name' => $code];
            if ($type === 'percent') {
                $couponParams['percent_off'] = $value;
            } else {
                $couponParams['amount_off'] = $value;
                $couponParams['currency'] = 'eur';
            }
            if ($expiresAt) {
                $couponParams['redeem_by'] = $expiresAt->getTimestamp();
            }
            $stripeCoupon = \Stripe\Coupon::create($couponParams);

            // Crear promotion code (API 2026)
            $promoParams = [
                'promotion' => [
                    'type'   => 'coupon',
                    'coupon' => $stripeCoupon->id,
                ],
                'code' => $code,
            ];

            $restrictions = [];
            if ($firstTimeOnly) {
                $restrictions['first_time_transaction'] = true;
            }
            if ($minimumAmountCents) {
                $restrictions['minimum_amount'] = $minimumAmountCents;
                $restrictions['minimum_amount_currency'] = 'eur';
            }
            if (!empty($restrictions)) {
                $promoParams['restrictions'] = $restrictions;
            }

            $promoCode = \Stripe\PromotionCode::create($promoParams);

            return [
                'coupon_id'         => $stripeCoupon->id,
                'promotion_code_id' => $promoCode->id,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe coupon creation failed', [
                'code'  => $code,
                'error' => $e->getMessage(),
            ]);
            return ['coupon_id' => null, 'promotion_code_id' => null];
        }
    }
}
