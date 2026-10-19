<?php

namespace App\Jobs;

use App\Models\MailCampaign;
use App\Models\MailSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SendCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 min per batch

    public int $tries = 1;

    public function __construct(
        public MailCampaign $campaign,
        public int $batchSize = 100,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $campaign = $this->campaign->fresh();

        // No enviar si la campaña está pausada o completada
        if (! in_array($campaign->status, ['sending'])) {
            Log::info("Campaign {$campaign->id} not in sending status, skipping batch");

            return;
        }

        // Lock atómico: marcar lote como 'sending' para evitar duplicados
        $sendIds = $campaign->pendingSends()
            ->limit($this->batchSize)
            ->pluck('id');

        if ($sendIds->isEmpty()) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            Log::info("Campaign {$campaign->id} completed — all sends processed");

            return;
        }

        // Reservar atómicamente como 'sending' — otro job no los cogerá
        $reserved = MailSend::whereIn('id', $sendIds)
            ->where('status', 'pending')
            ->update(['status' => 'sending']);

        if ($reserved === 0) {
            Log::info("Campaign {$campaign->id} batch: all rows already claimed by another job");

            return;
        }

        $sends = MailSend::whereIn('id', $sendIds)->with('user')->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($sends as $send) {
            // Skip si el usuario se desuscribió
            if ($send->user?->hasOptedOutOfMarketing()) {
                $send->update(['status' => 'failed', 'error_message' => 'User opted out']);
                $failedCount++;

                continue;
            }

            try {
                $locale = $send->detected_language;
                $subject = $campaign->t('subject', $locale);
                $body = $campaign->t('body', $locale);

                // Generar link de desuscripción firmado
                $unsubscribeUrl = URL::signedRoute('unsubscribe', ['email' => $send->email]);

                // URL del CTA según idioma
                $ctaUrl = $this->buildCtaUrl($locale, $campaign->coupon_code);

                Mail::html(
                    view('emails.campaign', [
                        'body' => $body,
                        'couponCode' => $campaign->coupon_code,
                        'ctaUrl' => $ctaUrl,
                        'unsubscribeUrl' => $unsubscribeUrl,
                        'locale' => $locale,
                        'userName' => $send->user?->first_name,
                    ])->render(),
                    function ($message) use ($send, $subject, $campaign) {
                        $message->to($send->email)
                            ->subject($subject);

                        // Header Acumbamail para categorizar campañas
                        $categoryName = 'genpic-'.Str::slug($campaign->name);
                        $message->getHeaders()->addTextHeader('ACUMBAMAIL-SMTPAPI', $categoryName);
                    }
                );

                $send->update(['status' => 'sent', 'sent_at' => now()]);
                $sentCount++;

            } catch (\Exception $e) {
                $send->update([
                    'status' => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
                $failedCount++;
                Log::warning('Campaign send failed', [
                    'campaign' => $campaign->id,
                    'email' => $send->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // Pequeña pausa entre emails para no saturar SMTP
            usleep(200_000); // 200ms = ~5 emails/segundo
        }

        // Actualizar contadores
        $campaign->increment('sent_count', $sentCount);
        $campaign->increment('failed_count', $failedCount);

        Log::info('Campaign batch completed', [
            'campaign' => $campaign->id,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'pending' => $campaign->fresh()->pendingSends()->count(),
        ]);
    }

    private function buildCtaUrl(string $locale, ?string $couponCode): string
    {
        $base = config('app.url');
        $prefix = $locale === 'es' ? '' : "/{$locale}";
        $url = "{$base}{$prefix}/create";
        if ($couponCode) {
            $url .= "?coupon={$couponCode}";
        }

        return $url;
    }

    /**
     * Si el job falla, devolver sends 'sending' a 'pending' para reintento.
     */
    public function failed(\Throwable $exception): void
    {
        $campaignId = $this->campaign?->id;

        if ($campaignId) {
            MailSend::where('campaign_id', $campaignId)
                ->where('status', 'sending')
                ->update(['status' => 'pending']);
        }

        Log::error('SendCampaignBatchJob failed — sends reverted to pending', [
            'campaign' => $campaignId,
            'error' => $exception->getMessage(),
        ]);
    }
}
