<?php

namespace App\Mail;

use App\Models\EmailSequence;
use App\Models\GenerationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $previewUrl;
    public string $checkoutUrl;
    public int $daysLeft;
    public ?string $couponCode;
    public int $discountPercent;
    public string $emailHeading;
    public string $emailBody;
    public string $emailCta;
    public string $emailCouponText;

    public function __construct(
        public readonly GenerationSession $session,
        public readonly EmailSequence $sequence,
        ?string $couponCode = null,
    ) {
        $locale = $session->locale ?? 'es';
        app()->setLocale($locale);

        $this->previewUrl = locale_route('create.preview', $session);
        $this->checkoutUrl = locale_route('checkout.index', $session);
        $this->daysLeft = max(0, (int) now()->diffInDays($session->expires_at, false));
        $this->couponCode = $couponCode;
        $this->discountPercent = $sequence->discount_percent;

        // Contenido desde BD, traducido al idioma del usuario
        $this->emailHeading = str_replace(':days', $this->daysLeft, $sequence->t('heading', $locale));
        $this->emailBody = str_replace(':days', $this->daysLeft, $sequence->t('body', $locale));
        $this->emailCta = $sequence->t('cta_text', $locale);
        $this->emailCouponText = $sequence->t('coupon_text', $locale);
    }

    public function envelope(): Envelope
    {
        $locale = $this->session->locale ?? 'es';
        return new Envelope(subject: $this->sequence->subject($locale));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abandonment');
    }
}
