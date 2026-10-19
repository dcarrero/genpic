<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProviderLog;
use App\Services\ImageGeneration\ImageGenerationService;
use App\Services\StorageService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Genera un retrato para un estilo extra (upsell).
 * Se usa dentro de un Bus::batch para procesar múltiples estilos en paralelo.
 * Cuando todo el batch termina, se despacha GenerateFormatsJob.
 */
class GenerateUpsellPortraitJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;
    public int $uniqueFor = 300;

    public function __construct(
        public Order $order,
        public OrderItem $item,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return 'upsell-' . $this->item->id;
    }

    public function handle(ImageGenerationService $service, StorageService $storage): void
    {
        if ($this->batch()?->cancelled()) return;

        $session = $this->order->session;
        $style = $this->item->artisticStyle;

        if (!$style || !$session->original_photo_path) {
            $this->item->update(['generation_status' => 'failed']);
            return;
        }

        $this->item->update(['generation_status' => 'processing']);

        $generation = Generation::create([
            'generation_session_id' => $session->id,
            'artistic_style_id'    => $style->id,
            'status'               => 'processing',
            'prompt_used'          => $style->base_prompt,
            'negative_prompt_used' => $style->negative_prompt,
            'style_strength_used'  => $style->style_strength,
            'queued_at'            => now(),
            'processing_started_at'=> now(),
            'attempt_number'       => $this->attempts(),
        ]);

        try {
            $imageRef = $session->original_photo_path;

            if ($storage->disk() === 'wasabi') {
                $imageRef = $storage->temporaryUrl($imageRef, now()->addMinutes(15));
            }

            $result = $service->generate([
                'image_url'       => $imageRef,
                'prompt'          => $style->base_prompt,
                'negative_prompt' => $style->negative_prompt,
                'style_strength'  => $style->style_strength,
            ]);

            $path = $storage->store($result['image'], 'generations', $session->uuid, 'png');

            $generation->update([
                'status'               => 'completed',
                'provider'             => $result['provider'],
                'provider_model'       => $result['model'],
                'seed'                 => $result['seed'],
                'raw_image_path'       => $path,
                'raw_image_size_bytes' => strlen($result['image']),
                'completed_at'         => now(),
                'elapsed_ms'           => $result['elapsed_ms'],
            ]);

            ProviderLog::record(
                session: $session,
                provider: $result['provider'],
                status: 'success',
                elapsedMs: $result['elapsed_ms'],
                costUsd: $result['cost_estimate'] ?? null,
                model: $result['model'],
                generation: $generation,
            );

            $this->item->update([
                'generation_id'     => $generation->id,
                'generation_status' => 'completed',
            ]);

            Log::info('Upsell portrait generated', [
                'order'   => $this->order->uuid,
                'style'   => $style->style_key,
                'elapsed' => $result['elapsed_ms'],
            ]);

        } catch (\Exception $e) {
            Log::error('Upsell portrait failed', [
                'order' => $this->order->uuid,
                'style' => $style->style_key,
                'error' => $e->getMessage(),
            ]);

            $generation->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            $this->item->update(['generation_status' => 'failed']);

            throw $e;
        }
    }
}
