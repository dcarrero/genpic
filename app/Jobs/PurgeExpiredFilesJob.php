<?php

namespace App\Jobs;

use App\Models\PurgeQueue;
use App\Services\StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PurgeExpiredFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(StorageService $storage): void
    {
        PurgeQueue::pending()->chunkById(50, function ($entries) use ($storage) {
        foreach ($entries as $entry) {
            try {
                // Lock + re-check dentro de transacción para evitar race con pagos
                $shouldPurge = \Illuminate\Support\Facades\DB::transaction(function () use ($entry) {
                    $locked = PurgeQueue::where('id', $entry->id)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->first();

                    if (!$locked) return false;

                    // Re-check orders pagadas con datos frescos (no snapshot)
                    $hasPaidOrder = $locked->session->orders()
                        ->whereIn('status', ['paid', 'processing', 'ready'])
                        ->exists();

                    if ($hasPaidOrder) {
                        Log::info("Purge skipped: session {$locked->session->uuid} tiene order pagada");
                        $locked->update(['status' => 'completed', 'processed_at' => now()]);
                        return false;
                    }

                    $locked->update(['status' => 'processing']);
                    return true;
                });

                if (!$shouldPurge) continue;

                // Borrar ficheros fuera de la transacción (operación lenta)
                $bytesFreed = 0;
                foreach ($entry->paths_to_purge as $path) {
                    if ($storage->exists($path)) {
                        $bytesFreed += $storage->size($path);
                        $storage->delete($path);
                    }
                }

                $entry->update([
                    'status'       => 'completed',
                    'processed_at' => now(),
                    'bytes_freed'  => $bytesFreed,
                ]);

                $entry->session->update(['status' => 'expired', 'purged_at' => now()]);

                Log::info("Purged session {$entry->session->uuid}", ['bytes_freed' => $bytesFreed]);

            } catch (\Exception $e) {
                $entry->update([
                    'status'        => 'error',
                    'error_message' => $e->getMessage(),
                ]);

                Log::error("Purge failed: {$entry->session->uuid}", ['error' => $e->getMessage()]);
            }
        }
        });
    }
}
