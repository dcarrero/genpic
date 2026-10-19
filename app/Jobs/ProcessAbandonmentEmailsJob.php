<?php

namespace App\Jobs;

use App\Mail\AbandonmentMail;
use App\Models\EmailSequence;
use App\Models\GenerationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAbandonmentEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $sequences = EmailSequence::active()->abandonment()
            ->orderBy('email_number')
            ->get();

        if ($sequences->isEmpty()) return;

        $maxEmailNumber = $sequences->max('email_number');

        // Sesiones con email, sin compra, no purgadas, últimos 14 días
        GenerationSession::query()
            ->whereNotNull('user_id')
            ->whereNotNull('email_captured_at')
            ->whereNull('purged_at')
            ->where('created_at', '>=', now()->subDays(14))
            ->where('abandonment_email_count', '<', $maxEmailNumber)
            // Sin ninguna order pagada
            ->whereDoesntHave('orders', fn ($q) => $q->whereIn('status', ['paid', 'ready', 'processing']))
            ->with('user')
            ->chunkById(50, function ($sessions) use ($sequences) {
                foreach ($sessions as $session) {
                    $this->processSession($session, $sequences);
                }
            });
    }

    private function processSession(
        GenerationSession $session,
        $sequences,
    ): void {
        if (!$session->user || str_starts_with($session->user->email, 'pending_')) return;

        // GDPR: respetar opt-out de marketing
        if ($session->user->hasOptedOutOfMarketing()) return;

        try {
            DB::transaction(function () use ($session, $sequences) {
                // Bloqueo pesimista para evitar que otro worker procese la misma sesión
                $lockedSession = GenerationSession::lockForUpdate()->find($session->id);
                if (!$lockedSession) return;

                $nextEmailNumber = $lockedSession->abandonment_email_count + 1;
                $sequence = $sequences->firstWhere('email_number', $nextEmailNumber);
                if (!$sequence) return;

                // Verificar timing: ¿han pasado suficientes horas desde creación?
                $hoursElapsed = $lockedSession->created_at->diffInHours(now());
                if ($hoursElapsed < $sequence->delay_hours) return;

                // Evitar enviar demasiado seguido (mínimo 4h entre emails)
                if ($lockedSession->abandonment_last_sent_at && $lockedSession->abandonment_last_sent_at->diffInHours(now()) < 4) return;

                // Cupón fijo FAMILY gestionado 100% en Stripe Dashboard
                $couponCode = $sequence->hasDiscount() ? 'FAMILY' : null;

                Mail::to($session->user)->send(
                    new AbandonmentMail($lockedSession, $sequence, $couponCode)
                );

                $lockedSession->update([
                    'abandonment_email_count'  => $nextEmailNumber,
                    'abandonment_last_sent_at' => now(),
                ]);

                Log::info('Abandonment email sent', [
                    'session'      => $lockedSession->uuid,
                    'email_number' => $nextEmailNumber,
                    'has_coupon'   => (bool) $couponCode,
                ]);
            });

        } catch (\Exception $e) {
            Log::warning('Abandonment email failed', [
                'session' => $session->uuid,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
