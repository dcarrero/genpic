<?php

namespace App\Mail;

use App\Models\GenerationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $accessUrl;
    public string $expiresAt;
    public int $daysLeft;

    public function __construct(
        public readonly GenerationSession $session
    ) {
        $locale = $session->locale ?? 'es';
        app()->setLocale($locale);
        $this->accessUrl = locale_route('portrait.access', ['session' => $session->uuid]);

        $this->expiresAt = $session->expires_at
            ? $session->expires_at->translatedFormat('d \d\e F \d\e Y')
            : now()->addDays(2)->translatedFormat('d \d\e F \d\e Y');

        $this->daysLeft = $session->expires_at
            ? (int) now()->diffInDays($session->expires_at, false)
            : 2;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('ui.email_subject_expiring', ['days' => $this->daysLeft]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.session-expiring');
    }
}
