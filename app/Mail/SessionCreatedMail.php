<?php

namespace App\Mail;

use App\Models\GenerationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $accessUrl;
    public string $expiresAt;

    public function __construct(
        public readonly GenerationSession $session
    ) {
        // URL de acceso seguro (pedirá email + PIN)
        $locale = $session->locale ?? 'es';
        app()->setLocale($locale);
        $this->accessUrl = locale_route('portrait.access', ['session' => $session->uuid]);

        $this->expiresAt = $session->expires_at
            ? $session->expires_at->translatedFormat('d \d\e F \d\e Y')
            : now()->addDays(14)->translatedFormat('d \d\e F \d\e Y');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('ui.email_subject_session'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.session-created');
    }
}
