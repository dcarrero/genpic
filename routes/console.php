<?php

use App\Jobs\ProcessAbandonmentEmailsJob;
use App\Jobs\ProcessUpgradeEmailsJob;
use App\Jobs\PurgeExpiredFilesJob;
use App\Mail\SessionExpiringMail;
use App\Models\GenerationSession;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

// Purga de archivos expirados — diariamente a las 03:00 Madrid
Schedule::job(new PurgeExpiredFilesJob)->dailyAt('03:00')->timezone('Europe/Madrid');

// Sincronizar orders pendientes con Stripe — cada 5 minutos
// Recupera pagos cuyo webhook no llegó (caída, timeout, etc.)
Schedule::command('stripe:sync-orders')->everyFiveMinutes();

// Aviso de expiración — 2 días antes del TTL, a las 09:00 Madrid
Schedule::call(function () {
    $ttlDays = (int) config('genpic.session_ttl_days', 14);
    $warningDate = now()->addDays(2);

    GenerationSession::whereNull('expiry_warning_sent_at')
        ->whereNull('purged_at')
        ->whereHas('user')
        ->where('created_at', '<=', now()->subDays($ttlDays - 2))
        ->where('created_at', '>', now()->subDays($ttlDays))
        ->chunkById(50, function ($sessions) {
            foreach ($sessions as $session) {
                if (! $session->user || str_starts_with($session->user->email, 'pending_')) {
                    continue;
                }

                // GDPR: respetar opt-out de marketing
                if ($session->user->hasOptedOutOfMarketing()) {
                    continue;
                }

                try {
                    Mail::to($session->user)->send(new SessionExpiringMail($session));
                    $session->update(['expiry_warning_sent_at' => now()]);
                } catch (Exception $e) {
                    // No bloquear el resto por un fallo individual
                }
            }
        });
})->dailyAt('09:00')->timezone('Europe/Madrid');

// Emails de abandono — cada hora (sesiones sin compra)
// Silenciados con CLOSED_MODE: son puro reclamo comercial y ya no hay nada que
// comprar. El aviso de expiracion de arriba si sigue, porque es informativo:
// avisa de que los archivos se borran y de que hay que descargarlos.
Schedule::job(new ProcessAbandonmentEmailsJob)
    ->hourly()
    ->withoutOverlapping()
    ->skip(fn () => config('genpic.closing.enabled'));

// Emails de upgrade — cada hora (clientes con compra parcial)
Schedule::job(new ProcessUpgradeEmailsJob)
    ->hourly()
    ->withoutOverlapping()
    ->skip(fn () => config('genpic.closing.enabled'));

// Re-sincronizar bans activos de BD a Redis — cinturón ante restarts de Redis
Schedule::command('moderation:sync-bans')->hourly();
