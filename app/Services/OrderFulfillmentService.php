<?php

namespace App\Services;

use App\Jobs\GenerateFormatsJob;
use App\Jobs\GenerateUpsellPortraitJob;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    /**
     * Confirma el pago de una order y despacha los jobs post-pago.
     * Punto único de entrada — usado por webhook, sync command, admin y modo simulado.
     *
     * @param  Order       $order
     * @param  string|null $stripePaymentIntentId
     * @param  string|null $stripeCustomerId
     * @param  string      $source  Identificador del caller (webhook, sync, admin, simulate)
     * @return bool        True si se procesó, false si ya estaba procesada
     */
    public function confirmPayment(
        Order $order,
        ?string $stripePaymentIntentId = null,
        ?string $stripeCustomerId = null,
        string $source = 'unknown',
        $checkoutSession = null
    ): bool {
        $processed = DB::transaction(function () use ($order, $stripePaymentIntentId, $stripeCustomerId, $source, $checkoutSession) {
            // Re-fetch con lock para evitar race conditions
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            // Idempotencia: no reprocesar
            if (in_array($order->status, ['paid', 'processing', 'ready'])) {
                Log::info('OrderFulfillment: order already processed', [
                    'order_uuid' => $order->uuid,
                    'status'     => $order->status,
                    'source'     => $source,
                ]);
                return false;
            }

            $updateData = [
                'status'  => 'paid',
                'paid_at' => now(),
            ];
            if ($stripePaymentIntentId) {
                $updateData['stripe_payment_intent_id'] = $stripePaymentIntentId;
            }
            if ($stripeCustomerId) {
                $updateData['stripe_customer_id'] = $stripeCustomerId;
            }

            // Reconciliar importes reales de Stripe
            if ($checkoutSession) {
                $updateData['stripe_amount_total'] = $checkoutSession->amount_total ?? null;
                $updateData['stripe_currency'] = $checkoutSession->currency ?? null;
                if ($totalDetails = $checkoutSession->total_details ?? null) {
                    $updateData['stripe_amount_discount'] = $totalDetails->amount_discount ?? 0;
                    $updateData['stripe_amount_tax'] = $totalDetails->amount_tax ?? 0;
                }
                // Promotion code usado (si aplica)
                $discounts = $checkoutSession->discounts ?? $checkoutSession->total_details->breakdown->discounts ?? [];
                if (!empty($discounts)) {
                    $firstDiscount = is_array($discounts) ? ($discounts[0] ?? null) : null;
                    if ($firstDiscount) {
                        $updateData['stripe_promotion_code'] = $firstDiscount->promotion_code ?? $firstDiscount->coupon->id ?? null;
                    }
                }
                // Actualizar total local si difiere (Stripe es fuente de verdad)
                if (isset($updateData['stripe_amount_total']) && $updateData['stripe_amount_total'] !== null) {
                    $updateData['total_cents'] = $updateData['stripe_amount_total'];
                }
                if (isset($updateData['stripe_amount_discount']) && $updateData['stripe_amount_discount'] > 0) {
                    $updateData['discount_cents'] = $updateData['stripe_amount_discount'];
                }
            }

            $order->update($updateData);
            $order->session->update(['status' => 'paid']);

            // Incrementar uso de cupón (query correcta para max_uses NULL)
            if ($order->coupon_code) {
                Coupon::where('code', $order->coupon_code)
                    ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('times_used', '<', 'max_uses'))
                    ->increment('times_used');
            }

            return true;
        });

        if (!$processed) {
            return false;
        }

        // Despachar jobs post-pago fuera de la transacción
        $this->dispatchPostPaymentJobs($order->fresh());

        Log::info('Pago confirmado', [
            'order_uuid' => $order->uuid,
            'total'      => $order->total_cents,
            'source'     => $source,
        ]);

        return true;
    }

    /**
     * Despacha jobs post-pago: si hay upsells, genera retratos en batch,
     * después GenerateFormatsJob. Si no hay upsells, solo GenerateFormatsJob.
     */
    public function dispatchPostPaymentJobs(Order $order): void
    {
        $upsellItems = $order->items()->where('is_upsell', true)->whereNull('generation_id')->get();

        if ($upsellItems->isNotEmpty()) {
            $jobs = $upsellItems->map(fn ($item) => new GenerateUpsellPortraitJob($order, $item));

            Bus::batch($jobs->all())
                ->then(fn () => GenerateFormatsJob::dispatch($order->fresh()))
                ->catch(function ($batch, $e) use ($order) {
                    Log::warning('Upsell batch partial failure', [
                        'order' => $order->uuid,
                        'error' => $e->getMessage(),
                    ]);
                    GenerateFormatsJob::dispatch($order->fresh());
                })
                ->name("upsell-{$order->uuid}")
                ->allowFailures()
                ->dispatch();
            return;
        }

        // Web basic/HD: ejecutar síncrono (pre-generados ya existen, <100ms)
        $baseProduct = $order->baseItem()?->product?->product_key;
        if (in_array($baseProduct, ['web_basic', 'web_hd']) && !$order->hasUpsellItems()) {
            GenerateFormatsJob::dispatchSync($order);
            return;
        }

        GenerateFormatsJob::dispatch($order);
    }
}
