<?php

namespace App\Jobs;

use App\Exceptions\ContentPolicyException;
use App\Mail\ContentViolationAdminMail;
use App\Mail\GenerationFailedMail;
use App\Models\ContentViolation;
use App\Models\Generation;
use App\Models\GenerationSession;
use App\Models\ProviderLog;
use App\Services\ContentModeration\IpBanService;
use App\Services\ImageGeneration\ImageGenerationService;
use App\Services\ImageGeneration\PromptBuilder;
use App\Services\StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GeneratePortraitJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;
    public int $uniqueFor = 300; // 5 min lock

    public function __construct(
        public GenerationSession $session,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return 'portrait-' . $this->session->id;
    }

    public function handle(ImageGenerationService $service, StorageService $storage): void
    {
        $style = $this->session->artisticStyle;

        $generation = Generation::create([
            'generation_session_id' => $this->session->id,
            'artistic_style_id'    => $style->id,
            'status'               => 'processing',
            'prompt_used'          => $style->base_prompt,
            'negative_prompt_used' => $style->negative_prompt,
            'style_strength_used'  => $style->style_strength,
            'queued_at'            => now(),
            'processing_started_at'=> now(),
            'attempt_number'       => $this->attempts(),
        ]);

        $this->session->update(['status' => 'processing']);

        try {
            // Pasar el path del storage — el provider se encarga de subirlo
            // a su CDN si es necesario (ej: fal.ai upload)
            $imageRef = $this->session->original_photo_path;

            // Si estamos en producción con Wasabi, usar signed URL
            if ($storage->disk() === 'wasabi') {
                $imageRef = $storage->temporaryUrl($imageRef, now()->addMinutes(15));
            }

            // Leer dimensiones de la foto sin descargar el archivo completo
            // getimagesize() con URL solo lee los headers necesarios
            $imageInfo = @getimagesize($imageRef);
            if (!$imageInfo) {
                // Fallback: leer desde storage local si URL falla
                $localPath = \Illuminate\Support\Facades\Storage::disk($storage->disk())
                    ->path($this->session->original_photo_path);
                $imageInfo = @getimagesize($localPath);
            }
            $origWidth = $imageInfo[0] ?? 1024;
            $origHeight = $imageInfo[1] ?? 1024;

            // Orientación elegida por el usuario (guardada en ab_variant)
            $orientation = $this->session->ab_variant ?? 'auto';

            // Calcular dimensiones según orientación elegida
            // Mantenemos la imagen original y dejamos que SeedReam trabaje con ella
            // El prompt incluirá instrucciones de orientación para que no corte caras
            $orientationPrompt = '';
            if ($orientation === 'vertical' && $origWidth > $origHeight) {
                $orientationPrompt = ' Adapt the composition to vertical/portrait orientation. Do NOT shift subjects vertically — keep heads at the same position with full visibility.';
            } elseif ($orientation === 'horizontal' && $origHeight > $origWidth) {
                $orientationPrompt = ' Adapt the composition to horizontal/landscape orientation. Do NOT shift subjects vertically — keep heads at the same position with full visibility.';
            } elseif ($orientation === 'square') {
                $orientationPrompt = ' Adapt the composition to a square 1:1 format. Do NOT shift subjects vertically — keep heads at the same position with full visibility.';
            }

            $result = $service->generate([
                'image_url'       => $imageRef,
                'prompt'          => $style->base_prompt . $orientationPrompt,
                'negative_prompt' => PromptBuilder::negativePrompt($style->negative_prompt),
                'style_strength'  => $style->style_strength,
                'width'           => $origWidth,
                'height'          => $origHeight,
                'orientation'     => $orientation,
            ]);

            // Guardar imagen generada en el disco correcto
            $path = $storage->store($result['image'], 'generations', $this->session->uuid, 'png');

            $generation->update([
                'status'             => 'completed',
                'provider'           => $result['provider'],
                'provider_model'     => $result['model'],
                'seed'               => $result['seed'],
                'raw_image_path'     => $path,
                'raw_image_size_bytes' => strlen($result['image']),
                'completed_at'       => now(),
                'elapsed_ms'         => $result['elapsed_ms'],
            ]);

            ProviderLog::record(
                session: $this->session,
                provider: $result['provider'],
                status: 'success',
                elapsedMs: $result['elapsed_ms'],
                costUsd: $result['cost_estimate'] ?? null,
                model: $result['model'],
                generation: $generation,
            );

            // Encadenar watermark
            ApplyWatermarkJob::dispatch($this->session, $generation);

        } catch (ContentPolicyException $e) {
            // CAPA 2: Proveedor IA rechazo por contenido prohibido
            Log::warning('GeneratePortraitJob: content policy violation', [
                'session'  => $this->session->uuid,
                'provider' => $e->providerName,
                'error'    => $e->originalMessage,
            ]);

            $generation->update([
                'status'        => 'failed',
                'error_message' => 'Content policy violation',
                'completed_at'  => now(),
            ]);

            $this->session->update(['status' => 'failed']);

            // Registrar violacion
            $fileHash = hash('sha256', $this->session->original_photo_path);
            $violation = ContentViolation::record(
                ip: $this->session->ip_address,
                email: $this->session->user?->email,
                userAgent: null,
                fileHash: $fileHash,
                fileName: $this->session->original_filename ?? null,
                fileSizeBytes: $this->session->original_size_bytes ?? null,
                violationType: 'content_policy',
                violationScore: null,
                provider: $e->providerName,
                providerResponse: ['error' => substr($e->originalMessage, 0, 1000)],
                locale: $this->session->locale,
                userId: $this->session->user_id,
            );

            // Banear IP
            $banHours = config('genpic.content_moderation.ban_duration_hours', 72);
            app(IpBanService::class)->ban($this->session->ip_address, $banHours, $violation->id);

            // Borrar imagen original del storage
            try {
                app(StorageService::class)->delete($this->session->original_photo_path);
            } catch (\Throwable $deleteErr) {
                Log::warning('No se pudo borrar imagen original tras violacion', ['error' => $deleteErr->getMessage()]);
            }

            // Notificar admin (rate-limited)
            $this->notifyAdminContentViolation($violation, $banHours);

            // NO reintentar — delete del job
            $this->delete();
            return;

        } catch (\Exception $e) {
            Log::error('GeneratePortraitJob exception', [
                'session' => $this->session->uuid,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $generation->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);

            $this->session->update(['status' => 'failed']);

            ProviderLog::record(
                session: $this->session,
                provider: 'unknown',
                status: 'error',
                errorMessage: $e->getMessage(),
                generation: $generation,
            );

            throw $e;
        }
    }

    /**
     * Notificar a admins de violacion de contenido detectada por proveedor IA.
     */
    private function notifyAdminContentViolation(ContentViolation $violation, int $banHours): void
    {
        try {
            $cacheKey = "admin_notified_violation_{$violation->ip_address}";
            if (Cache::has($cacheKey)) {
                return;
            }

            $adminEmails = config('genpic.admin_emails', []);
            if (empty($adminEmails)) {
                return;
            }

            $mail = new ContentViolationAdminMail(
                ipAddress: $violation->ip_address,
                email: $violation->email,
                userAgent: null,
                violationType: $violation->violation_type,
                violationScore: $violation->violation_score,
                fileHash: $violation->file_hash,
                provider: $violation->provider,
                ipBanned: true,
                banHours: $banHours,
            );

            foreach ($adminEmails as $email) {
                Mail::to($email)->send($mail);
            }

            Cache::put($cacheKey, true, 900);
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar email admin de violacion', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Llamado por Laravel después de agotar todos los reintentos.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GeneratePortraitJob failed permanently', [
            'session' => $this->session->uuid,
            'error'   => $exception->getMessage(),
        ]);

        $this->session->update(['status' => 'failed']);

        try {
            $user = $this->session->user;
            if ($user) {
                Mail::to($user)->send(new GenerationFailedMail($this->session));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send GenerationFailedMail', ['error' => $e->getMessage()]);
        }
    }
}
