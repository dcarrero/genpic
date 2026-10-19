<?php

namespace App\Mail;

use App\Models\GenerationSession;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class FreeDownloadMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $downloadUrl;
    public string $upsellUrl;
    public string $couponCode = 'PROMO50';

    public function __construct(
        public readonly Order $order,
        public readonly GenerationSession $session,
    ) {
        $locale = $session->locale ?? 'es';
        app()->setLocale($locale);

        // Signed URL a página de descarga intermedia (14 días)
        $this->downloadUrl = URL::signedRoute('free-download.page', [
            'order' => $order->uuid,
        ], now()->addDays(14));

        // Link directo a checkout con email + cupón pre-rellenados
        $email = $order->user?->email ?? '';
        $this->upsellUrl = locale_route('checkout.index', $session)
            . '?product=pack_completo'
            . '&email=' . urlencode($email)
            . '&coupon=' . $this->couponCode;
    }

    public function envelope(): Envelope
    {
        $locale = $this->session->locale ?? 'es';
        $subject = match ($locale) {
            'en' => 'Your free portrait is ready! Download it now',
            'fr' => 'Votre portrait gratuit est prêt ! Téléchargez-le',
            'pt' => 'O seu retrato grátis está pronto! Descarregue-o',
            'it' => 'Il tuo ritratto gratis è pronto! Scaricalo',
            default => 'Tu retrato gratis está listo! Descárgalo',
        };

        return new Envelope(subject: $subject . ' — GenPic');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.free-download');
    }
}
