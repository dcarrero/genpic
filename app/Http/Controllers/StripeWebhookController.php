<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature failed', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        if (in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
        ])) {
            $this->handlePaymentConfirmed($event->data->object, $event->type);
        }

        return response('OK', 200);
    }

    private function handlePaymentConfirmed($checkoutSession, string $eventType): void
    {
        // Para checkout.session.completed, verificar que el pago ya esté confirmado
        if ($eventType === 'checkout.session.completed'
            && ($checkoutSession->payment_status ?? null)
            && $checkoutSession->payment_status !== 'paid') {
            Log::info('Stripe webhook: payment not yet confirmed (async), waiting for async_payment_succeeded', [
                'checkout_id'    => $checkoutSession->id,
                'payment_status' => $checkoutSession->payment_status,
            ]);
            return;
        }

        $order = Order::where('stripe_checkout_id', $checkoutSession->id)->first();

        if (!$order) {
            Log::warning('Stripe webhook: order not found', ['checkout_id' => $checkoutSession->id]);
            return;
        }

        app(OrderFulfillmentService::class)->confirmPayment(
            $order,
            $checkoutSession->payment_intent,
            $checkoutSession->customer,
            "webhook:{$eventType}",
            $checkoutSession
        );
    }
}
