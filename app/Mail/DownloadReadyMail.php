<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class DownloadReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $downloadLinks;
    public string $orderedAt;
    public string $downloadPageUrl;

    public function __construct(
        public readonly Order $order
    ) {
        // URL a la página de descarga (checkout success con order)
        $locale = $order->generationSession?->locale ?? 'es';
        app()->setLocale($locale);
        $this->downloadPageUrl = locale_route('checkout.success') . '?order=' . $order->uuid;

        // Links por formato
        $this->downloadLinks = collect();
        $expiresAt = $order->created_at->addDays((int) config('genpic.session_ttl_days', 14));
        foreach ($order->items as $item) {
            if (!$item->export_paths) continue;
            foreach ($item->export_paths as $formatKey => $path) {
                $formatConfig = config("genpic.formats.{$formatKey}");
                $ext = strtoupper($formatConfig['type'] ?? pathinfo($path, PATHINFO_EXTENSION));
                $dim = $formatConfig['max_dimension'] ?? null;
                $description = $dim ? "{$ext} · {$dim}px" : $ext;

                $this->downloadLinks->push([
                    'label'       => str_replace('_', ' ', ucfirst($formatKey)),
                    'description' => $description,
                    'format'      => $formatKey,
                    'url'         => URL::temporarySignedRoute('secure.export', now()->addHours(72), ['order' => $order->uuid, 'format' => $formatKey]),
                    'expires_at'  => $expiresAt->translatedFormat('d \d\e F \d\e Y'),
                ]);
            }
        }

        $this->orderedAt = $order->created_at->translatedFormat('d \d\e F \d\e Y');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('ui.email_subject_download'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.download-ready');
    }
}
