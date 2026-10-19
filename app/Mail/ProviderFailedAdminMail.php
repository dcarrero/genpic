<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderFailedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $providerName,
        public readonly string $errorMessage,
        public readonly bool $isTimeout,
        public readonly int $cooldownSeconds,
        public readonly bool $allFailed = false,
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->allFailed ? '[CRITICO]' : '[AVISO]';
        return new Envelope(
            subject: "{$prefix} GenPic IA — {$this->providerName} falló"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.provider-failed-admin');
    }
}
