<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza orders pendientes con Stripe.
 *
 * Busca orders con stripe_checkout_id que están en estado 'pending'
 * y verifica en Stripe si el pago se completó. Si sí, actualiza
 * la order y despacha GenerateFormatsJob.
 *
 * Útil para:
 * - Webhooks que no llegaron (caída del servidor, listener apagado)
 * - Reconciliación manual
 * - Cron de seguridad (ejecutar cada 5 minutos)
 */
class StripeSyncOrders extends Command
{
    protected $signature = 'stripe:sync-orders
        {--order= : UUID de una order específica}
        {--dry-run : Solo mostrar, no actualizar}';

    protected $description = 'Sincroniza orders pendientes con Stripe — recupera pagos que no recibieron webhook';

    public function handle(): int
    {
        $stripeSecret = config('services.stripe.secret');
        if (!$stripeSecret) {
            $this->error('STRIPE_SECRET no configurado');
            return self::FAILURE;
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);
        $dryRun = $this->option('dry-run');

        // Buscar orders pendientes con checkout_id
        $query = Order::where('status', 'pending')
            ->whereNotNull('stripe_checkout_id')
            ->where('created_at', '>=', now()->subDays(14)); // Solo últimos 14 días

        if ($orderUuid = $this->option('order')) {
            $query->where('uuid', $orderUuid);
        }

        $pendingOrders = $query->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('No hay orders pendientes con checkout_id.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$pendingOrders->count()} orders pendientes. Verificando en Stripe...");

        $synced = 0;
        $skipped = 0;

        foreach ($pendingOrders as $order) {
            try {
                $cs = $stripe->checkout->sessions->retrieve($order->stripe_checkout_id);

                $statusLine = sprintf(
                    '  %s | %s | %s | %s€',
                    substr($order->uuid, 0, 8),
                    $cs->payment_status,
                    $cs->status,
                    number_format($order->total_cents / 100, 2)
                );

                if ($cs->payment_status === 'paid' && $cs->status === 'complete') {
                    if ($dryRun) {
                        $this->warn("{$statusLine} → SE ACTUALIZARÍA (dry-run)");
                    } else {
                        app(OrderFulfillmentService::class)->confirmPayment(
                            $order,
                            $cs->payment_intent,
                            $cs->customer,
                            'stripe:sync-orders',
                            $cs
                        );

                        $this->info("{$statusLine} → SINCRONIZADO");
                    }
                    $synced++;
                } else {
                    $this->line("{$statusLine} → no pagado, saltando");
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error("  {$order->uuid}: Error Stripe — {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Resultado: {$synced} sincronizadas, {$skipped} saltadas.");

        return self::SUCCESS;
    }
}
