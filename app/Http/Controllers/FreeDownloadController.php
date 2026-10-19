<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\URL;

class FreeDownloadController extends Controller
{
    public function show(Order $order)
    {
        // Solo orders gratuitas
        if ($order->total_cents > 0) {
            abort(404);
        }

        $session = $order->session;
        $locale = $session?->locale ?? 'es';
        app()->setLocale($locale);

        // URL de descarga directa (signed, 14 días)
        $directDownloadUrl = URL::signedRoute('secure.export', [
            'order'  => $order->uuid,
            'format' => 'web_basic',
        ], now()->addDays(14));

        // URL de upsell al checkout
        $email = $order->user?->email ?? '';
        $upsellUrl = locale_route('checkout.index', $session)
            . '?product=pack_completo'
            . '&email=' . urlencode($email)
            . '&coupon=PROMO50';

        return view('free-download.show', compact('order', 'session', 'directDownloadUrl', 'upsellUrl', 'locale'));
    }
}
