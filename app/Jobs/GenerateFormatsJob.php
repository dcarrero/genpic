<?php

namespace App\Jobs;

use App\Mail\DownloadReadyMail;
use App\Models\Order;
use App\Services\ImageMetadataService;
use App\Services\ImageGeneration\ImageGenerationService;
use App\Services\StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class GenerateFormatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 min — puede necesitar regenerar + upscale
    public int $tries = 2;

    public function __construct(
        public Order $order,
    ) {
        $this->onQueue('default');
    }

    public function handle(StorageService $storage): void
    {
        $this->order->update(['status' => 'processing']);

        $manager = new ImageManager(
            \Intervention\Image\Drivers\Imagick\Driver::class
        );

        $disk = $storage->disk();

        // Eager load para evitar N+1
        $this->order->loadMissing(['items.generation', 'items.product.formats', 'session.artisticStyle']);

        foreach ($this->order->items as $item) {
            $generation = $item->generation;
            if (!$generation || !$generation->raw_image_path) continue;

            $rawImage = Storage::disk($disk)->get($generation->raw_image_path);
            $product = $item->product;
            $formats = $product->formats;
            $exportPaths = [];
            $session = $this->order->session;

            foreach ($formats as $format) {
                $formatConfig = config("genpic.formats.{$format->format_key}");
                if (!$formatConfig) continue;

                Log::info("GenerateFormats: {$format->format_key}", ['order' => $this->order->uuid]);

                // --- Web Basic / Web HD: usar pre-generados si existen ---
                $preGenPath = "exports/{$session->uuid}/{$format->format_key}_genpic_com.jpg";
                if (in_array($format->format_key, ['web_basic', 'web_hd']) && Storage::disk($disk)->exists($preGenPath)) {
                    Log::info("Using pre-generated: {$format->format_key}");
                    $exportPaths[$format->format_key] = $preGenPath;
                    continue;
                }

                // --- Original PNG: el PNG de la IA, con upscale a 4096px+ si necesario ---
                if (!empty($formatConfig['raw'])) {
                    $rawInfo = getimagesizefromstring($rawImage);
                    $rawMaxDim = max($rawInfo[0] ?? 0, $rawInfo[1] ?? 0);
                    $minDim = 4096;

                    if ($rawMaxDim >= $minDim) {
                        Log::info("Original PNG: raw ya es {$rawMaxDim}px, entrega directa");
                        $encoded = $rawImage;
                    } else {
                        Log::info("Original PNG: raw {$rawMaxDim}px < {$minDim}px, upscaling");
                        $encoded = $this->handleUpscale($rawImage, ['max_dimension' => $minDim], $manager);
                    }
                }
                // --- Wallpapers: regenerar si orientación no coincide ---
                elseif (!empty($formatConfig['regenerate']) && isset($formatConfig['width'], $formatConfig['height'])) {
                    $encoded = $this->handleWallpaper(
                        $rawImage, $formatConfig, $generation, $session, $storage, $manager
                    );
                }
                // --- Print Premium: upscale solo si raw < max_dimension ---
                elseif (!empty($formatConfig['upscale'])) {
                    $maxDim = $formatConfig['max_dimension'] ?? 4096;
                    $rawInfo = getimagesizefromstring($rawImage);
                    $rawMaxDim = max($rawInfo[0] ?? 0, $rawInfo[1] ?? 0);

                    if ($rawMaxDim >= $maxDim) {
                        // Ya tenemos 4K+ de la IA, no hace falta upscale
                        Log::info("Print premium: raw ya es {$rawMaxDim}px, sin upscale");
                        $image = $manager->read($rawImage);
                        $image->scaleDown($maxDim);
                        $encoded = $image->toPng()->toString();
                    } else {
                        $encoded = $this->handleUpscale($rawImage, $formatConfig, $manager);
                    }
                }
                // --- Fallback: simple resize (si pre-gen no existía) ---
                else {
                    $image = $manager->read($rawImage);
                    if (isset($formatConfig['max_dimension'])) {
                        $image->scaleDown($formatConfig['max_dimension']);
                    }
                    $ext = $formatConfig['type'] ?? 'jpg';
                    $encoded = $ext === 'png'
                        ? $image->toPng()->toString()
                        : $image->toJpeg($formatConfig['quality'] ?? 85)->toString();
                }

                // Añadir metadatos IPTC/XMP/Comment en el idioma de la sesión
                $locale = $session->locale ?? 'es';
                $styleName = $session->artisticStyle?->t('label', $locale) ?? $session->style_key;
                $formatLabel = $format->t('label', $locale) ?: $format->format_key;
                $encoded = ImageMetadataService::applyToContent($encoded, $styleName, $formatLabel, $locale);

                $ext = $formatConfig['type'] ?? 'jpg';
                $exportPath = sprintf('exports/%s/%s_%s_genpic_com.%s', $session->uuid, $format->format_key, Str::random(8), $ext);
                Storage::disk($disk)->put($exportPath, $encoded);
                $exportPaths[$format->format_key] = $exportPath;
            }

            $item->update([
                'export_paths'       => $exportPaths,
                'exports_ready'      => true,
                'exports_size_bytes' => collect($exportPaths)->sum(fn ($p) => $storage->size($p)),
            ]);
        }

        $allReady = $this->order->fresh()->allItemsReady();
        $this->order->update([
            'status'         => $allReady ? 'ready' : 'processing',
            'download_ready' => $allReady,
        ]);

        // Email de descarga — solo si todo listo y no enviado antes
        if ($allReady && !$this->order->download_email_sent_at) {
            try {
                $user = $this->order->session?->user;
                if ($user && !str_starts_with($user->email, 'pending_')) {
                    Mail::to($user)->send(new DownloadReadyMail($this->order));
                    $this->order->update(['download_email_sent_at' => now()]);
                }
            } catch (\Exception $e) {
                Log::warning('DownloadReadyMail failed', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Wallpaper: si orientación no coincide, usar blur background + imagen centrada.
     * NUNCA recortar — no queremos cortar caras/personas.
     */
    private function handleWallpaper(
        string $rawImage, array $formatConfig, $generation, $session, StorageService $storage, ImageManager $manager
    ): string {
        $targetW = $formatConfig['width'];
        $targetH = $formatConfig['height'];
        $quality = $formatConfig['quality'] ?? 90;

        $source = $manager->read($rawImage);
        $srcW = $source->width();
        $srcH = $source->height();

        $targetRatio = $targetW / $targetH;
        $srcRatio = $srcW / $srcH;

        // Si el ratio es similar (dentro de 10%), resize directo
        if (abs($targetRatio - $srcRatio) / $targetRatio < 0.10) {
            $source->resize($targetW, $targetH);
            return $source->toJpeg($quality)->toString();
        }

        // Ratio diferente: blur background + imagen nítida centrada
        Log::info("Wallpaper: blur background for {$targetW}x{$targetH}");

        // 1. Crear fondo: la misma imagen escalada y desenfocada
        $bg = $manager->read($rawImage);
        $bg->cover($targetW, $targetH);

        // Aplicar blur via Imagick nativo
        $bgImagick = $bg->core()->native();
        $bgImagick->blurImage(30, 15);
        // Oscurecer un poco el fondo
        $bgImagick->modulateImage(70, 80, 100); // brightness 70%, saturation 80%

        // 2. Escalar la imagen original para que quepa completa (contain)
        $sharp = $manager->read($rawImage);
        $sharp->scaleDown($targetW, $targetH);

        // 3. Calcular posición centrada
        $offsetX = (int) (($targetW - $sharp->width()) / 2);
        $offsetY = (int) (($targetH - $sharp->height()) / 2);

        // 4. Pegar imagen nítida sobre fondo blur
        $bg->place($sharp, 'top-left', $offsetX, $offsetY);

        return $bg->toJpeg($quality)->toString();
    }

    /**
     * Print Premium: upscale 2x con Real-ESRGAN en fal.ai.
     */
    private function handleUpscale(string $rawImage, array $formatConfig, ImageManager $manager): string
    {
        $maxDim = $formatConfig['max_dimension'] ?? 4096;
        $startTime = microtime(true);

        try {
            // Subir imagen a fal.ai CDN
            $falKey = config('image_providers.providers.fal_ai.key');

            $initResponse = Http::withHeaders([
                'Authorization' => 'Key ' . $falKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://rest.alpha.fal.ai/storage/upload/initiate', [
                'content_type' => 'image/png',
                'file_name'    => 'upscale_source.png',
            ]);

            $uploadUrl = $initResponse->json('upload_url');
            $fileUrl = $initResponse->json('file_url');

            Http::withHeaders(['Content-Type' => 'image/png'])
                ->timeout(60)
                ->withBody($rawImage, 'image/png')
                ->put($uploadUrl);

            // Upscale con Real-ESRGAN
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $falKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://fal.run/fal-ai/real-esrgan', [
                'image_url' => $fileUrl,
                'scale'     => 2,
            ]);

            if ($response->successful() && $url = $response->json('image.url')) {
                $upscaled = Http::timeout(60)->get($url)->body();
                $image = $manager->read($upscaled);
                $image->scaleDown($maxDim);

                // Registrar coste de upscale
                $upscaleCost = config('image_providers.upscale.cost_per_image', 0.010);
                Log::info('Upscale completed', [
                    'order'    => $this->order->uuid,
                    'provider' => config('image_providers.upscale.provider', 'fal_ai'),
                    'cost_usd' => $upscaleCost,
                ]);

                // Log en provider_logs para estadísticas
                \App\Models\ProviderLog::create([
                    'generation_session_id' => $this->order->session?->id,
                    'provider'              => config('image_providers.upscale.provider', 'fal_ai'),
                    'model'                 => 'real-esrgan',
                    'status'                => 'success',
                    'cost_usd'              => $upscaleCost,
                    'elapsed_ms'            => (int) round((microtime(true) - $startTime) * 1000),
                ]);

                return $image->toPng()->toString();
            }
        } catch (\Exception $e) {
            Log::warning("Upscale failed, using original size", [
                'order' => $this->order->uuid,
                'error' => $e->getMessage(),
            ]);

            // Log error en provider_logs para estadísticas
            \App\Models\ProviderLog::create([
                'generation_session_id' => $this->order->session?->id,
                'provider'              => config('image_providers.upscale.provider', 'fal_ai'),
                'model'                 => 'real-esrgan',
                'status'                => 'error',
                'cost_usd'              => 0,
                'elapsed_ms'            => (int) round((microtime(true) - $startTime) * 1000),
                'error_message'         => substr($e->getMessage(), 0, 500),
            ]);
        }

        // Fallback: la imagen original al tamaño que tenga (degradado)
        Log::warning("Order {$this->order->uuid}: print format served at original resolution (upscale failed)");
        $image = $manager->read($rawImage);
        return $image->toPng()->toString();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateFormatsJob failed permanently', [
            'order' => $this->order->uuid,
            'error' => $exception->getMessage(),
        ]);

        $this->order->update(['status' => 'failed']);
    }
}
