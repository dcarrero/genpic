<?php

namespace App\Jobs;

use App\Mail\UpgradeMail;
use App\Models\EmailSequence;
use App\Models\GenerationSession;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessUpgradeEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Productos que cualifican para upgrade
    private const UPGRADE_FROM = ['web_basic', 'web_hd', 'wallpaper_pack'];

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $sequences = EmailSequence::active()->upgrade()
            ->orderBy('email_number')
            ->get();

        if ($sequences->isEmpty()) return;

        $maxEmailNumber = $sequences->max('email_number');

        // Sesiones con compra parcial (no pack_completo), últimos 14 días
        GenerationSession::query()
            ->whereNotNull('user_id')
            ->whereNull('purged_at')
            ->where('created_at', '>=', now()->subDays(14))
            ->where('upgrade_email_count', '<', $maxEmailNumber)
            // Tiene al menos una order pagada
            ->whereHas('orders', function ($q) {
                $q->whereIn('status', ['paid', 'ready'])
                  ->whereHas('items', function ($q2) {
                      $q2->whereHas('product', fn ($q3) => $q3->whereIn('product_key', self::UPGRADE_FROM));
                  });
            })
            // Pero NO tiene pack_completo
            ->whereDoesntHave('orders', function ($q) {
                $q->whereIn('status', ['paid', 'ready'])
                  ->whereHas('items', function ($q2) {
                      $q2->whereHas('product', fn ($q3) => $q3->where('product_key', 'pack_completo'));
                  });
            })
            ->with(['user', 'orders' => fn ($q) => $q->whereIn('status', ['paid', 'ready'])->orderBy('paid_at')])
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

        // Conservar las orders eager-loaded para el timing check
        $existingOrder = $session->orders->first();
        if (!$existingOrder || !$existingOrder->paid_at) return;

        try {
            DB::transaction(function () use ($session, $sequences, $existingOrder) {
                // Bloqueo pesimista para evitar que otro worker procese la misma sesión
                $lockedSession = GenerationSession::lockForUpdate()->find($session->id);
                if (!$lockedSession) return;

                $nextEmailNumber = $lockedSession->upgrade_email_count + 1;
                $sequence = $sequences->firstWhere('email_number', $nextEmailNumber);
                if (!$sequence) return;

                // Verificar timing desde la compra original
                $hoursSincePurchase = $existingOrder->paid_at->diffInHours(now());
                if ($hoursSincePurchase < $sequence->delay_hours) return;

                // Mínimo 12h entre emails de upgrade
                if ($lockedSession->upgrade_last_sent_at && $lockedSession->upgrade_last_sent_at->diffInHours(now()) < 12) return;

                // Cupón fijo FAMILY gestionado 100% en Stripe Dashboard
                $couponCode = $sequence->hasDiscount() ? 'FAMILY' : null;

                Mail::to($session->user)->send(
                    new UpgradeMail($lockedSession, $sequence, $existingOrder, $couponCode)
                );

                $lockedSession->update([
                    'upgrade_email_count'  => $nextEmailNumber,
                    'upgrade_last_sent_at' => now(),
                ]);

                Log::info('Upgrade email sent', [
                    'session'      => $lockedSession->uuid,
                    'email_number' => $nextEmailNumber,
                    'has_coupon'   => (bool) $couponCode,
                ]);
            });

        } catch (\Exception $e) {
            Log::warning('Upgrade email failed', [
                'session' => $session->uuid,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
