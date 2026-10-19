<?php

namespace App\Mail;

use App\Models\GenerationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenerationFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $newGenerationUrl;

    public function __construct(
        public readonly GenerationSession $session
    ) {
        // Usar locale_route para que el link apunte al idioma correcto
        $locale = $session->locale ?? 'es';
        app()->setLocale($locale);

        $this->newGenerationUrl = locale_route('create.index', [
            'style' => $session->style_key,
        ]);
    }

    public function envelope(): Envelope
    {
        $locale = $this->session->locale ?? 'es';
        return new Envelope(subject: __('ui.email_subject_failed', [], $locale));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.generation-failed');
    }
}
