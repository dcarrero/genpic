<?php

namespace App\Jobs;

use App\Mail\SessionCreatedMail;
use App\Models\Generation;
use App\Models\GenerationSession;
use App\Services\ImageMetadataService;
use App\Services\StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ApplyWatermarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public GenerationSession $session,
        public Generation $generation,
    ) {
        $this->onQueue('default');
    }

    public function handle(StorageService $storage): void
    {
        $config = config('genpic.watermark');

        $imageContent = Storage::disk($storage->disk())->get($this->generation->raw_image_path);

        $manager = new ImageManager(
            \Intervention\Image\Drivers\Imagick\Driver::class
        );

        // --- Pre-generar exports web (limpios, sin watermark) ---
        // Así la compra de web_basic/web_hd es instantánea
        $this->preGenerateWebExports($imageContent, $manager, $storage);

        $image = $manager->read($imageContent);

        // 1. Orientación: solo recortar si el usuario pidió CAMBIAR la orientación
        $orientation = $this->session->ab_variant ?? 'auto';
        $width = $image->width();
        $height = $image->height();
        $isVertical = $height > $width;
        $isHorizontal = $width > $height;

        if ($orientation === 'vertical' && $isHorizontal) {
            $targetWidth = (int) ($height * 3 / 4);
            $targetHeight = $height;
            $image->crop($targetWidth, $targetHeight, (int) (($width - $targetWidth) / 2), 0);
        } elseif ($orientation === 'horizontal' && $isVertical) {
            $targetWidth = $width;
            $targetHeight = (int) ($width * 3 / 4);
            $offsetY = (int) (($height - $targetHeight) / 2);
            $image->crop($targetWidth, $targetHeight, 0, $offsetY);
        } elseif ($orientation === 'square' && $width !== $height) {
            $side = min($width, $height);
            $image->crop($side, $side, (int) (($width - $side) / 2), (int) (($height - $side) / 2));
        }

        // 2. Reducir a preview (max 600px ancho)
        $image->scaleDown(600);
        $width = $image->width();
        $height = $image->height();

        // 3. Aplicar watermark denso
        $text = $config['text'];
        $fontSize = $config['font_size'];
        $spacing = $config['spacing_px'];

        for ($y = -30; $y < $height + 30; $y += $spacing) {
            for ($x = -150; $x < $width + 150; $x += $spacing) {
                $image->text($text, $x, $y, function ($font) use ($fontSize) {
                    $font->filename(storage_path('fonts/DejaVuSansMono-Bold.ttf'));
                    $font->size($fontSize);
                    $font->color('rgba(255, 255, 255, 0.50)');
                    $font->angle(35);
                });
            }
        }

        // 4. Metadatos + guardar watermark
        $locale = $this->session->locale ?? 'es';
        $styleName = $this->session->artisticStyle?->t('label', $locale) ?? $this->session->style_key;
        $formatLabel = match ($locale) {
            'en' => 'Watermarked preview',
            'fr' => 'Aperçu avec filigrane',
            'pt' => 'Pré-visualização com marca d\'água',
            'it' => 'Anteprima con filigrana',
            default => 'Preview con marca de agua',
        };
        ImageMetadataService::apply($image->core()->native(), $styleName, $formatLabel, $locale);

        $watermarkedPath = $storage->store(
            $image->toJpeg(80)->toString(),
            'watermarked',
            $this->session->uuid,
            'jpg'
        );

        $this->generation->update([
            'status'                 => 'watermarked',
            'watermarked_image_path' => $watermarkedPath,
        ]);

        $this->session->update(['status' => 'watermarked']);

        Log::info('Watermark applied', [
            'session'     => $this->session->uuid,
            'orientation' => $orientation,
            'size'        => $width . 'x' . $height,
        ]);

        // Notificar por email si el usuario dejó su email
        $this->notifyUserByEmail();
    }

    /**
     * Envía email de notificación al usuario cuando el retrato está listo.
     * Solo si tiene email y no empieza por 'pending_'.
     */
    private function notifyUserByEmail(): void
    {
        try {
            $user = $this->session->user;
            if (!$user || str_starts_with($user->email, 'pending_')) {
                return;
            }

            // No enviar si ya se envió antes (evitar duplicados en reintentos)
            if ($this->session->session_email_sent_at) {
                return;
            }

            Mail::to($user)->send(new SessionCreatedMail($this->session));
            $this->session->update(['session_email_sent_at' => now()]);

            Log::info('Portrait ready notification sent', [
                'session' => $this->session->uuid,
                'email'   => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::warning('Portrait ready notification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Manejo de fallo permanente: marca generation y session como failed.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('ApplyWatermarkJob failed permanently', [
            'generation_id' => $this->generation->id,
            'session_uuid'  => $this->session->uuid,
            'error'         => $e->getMessage(),
        ]);

        $this->generation->update(['status' => 'failed']);
        $this->session->update(['status' => 'failed']);
    }

    /**
     * Pre-genera exports web (limpio, sin watermark) para que la compra sea instantánea.
     * Guarda web_hd (2048px) y web_basic (1024px) en exports/{uuid}/.
     */
    private function preGenerateWebExports(string $imageContent, ImageManager $manager, StorageService $storage): void
    {
        try {
            $uuid = $this->session->uuid;
            $disk = $storage->disk();
            $locale = $this->session->locale ?? 'es';
            $styleName = $this->session->artisticStyle?->t('label', $locale) ?? $this->session->style_key;

            // Web HD — 2048px, JPG Q90
            $hdImage = $manager->read($imageContent);
            $hdImage->scaleDown(2048);
            ImageMetadataService::apply($hdImage->core()->native(), $styleName, 'Web HD 2048px', $locale);
            $hdPath = "exports/{$uuid}/web_hd_genpic_com.jpg";
            Storage::disk($disk)->put($hdPath, $hdImage->toJpeg(90)->toString());

            // Web Basic — 1024px, JPG Q85
            $basicImage = $manager->read($imageContent);
            $basicImage->scaleDown(1024);
            ImageMetadataService::apply($basicImage->core()->native(), $styleName, 'Web Basic 1024px', $locale);
            $basicPath = "exports/{$uuid}/web_basic_genpic_com.jpg";
            Storage::disk($disk)->put($basicPath, $basicImage->toJpeg(85)->toString());

            Log::info('Web exports pre-generated with metadata', ['session' => $uuid]);
        } catch (\Exception $e) {
            Log::warning('Pre-generate web exports failed', ['error' => $e->getMessage()]);
        }
    }
}
