<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContentViolationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $ipAddress,
        public readonly ?string $email,
        public readonly ?string $userAgent,
        public readonly string $violationType,
        public readonly ?float $violationScore,
        public readonly string $fileHash,
        public readonly string $provider,
        public readonly bool $ipBanned,
        public readonly int $banHours,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[ALERTA] GenPic — Contenido prohibido ({$this->violationType})"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.content-violation-admin');
    }
}
