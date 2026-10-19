<?php

namespace App\Services\ContentModeration\Providers;

use App\Services\ContentModeration\Contracts\ModerationProviderInterface;
use App\Services\ContentModeration\ModerationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hugging Face Inference API — modelo Falconsai/nsfw_image_detection
 * Free tier: 30K requests/mes sin tarjeta.
 * Clasifica imagenes como "nsfw" o "normal" con score de confianza.
 */
class HuggingFaceNsfwProvider implements ModerationProviderInterface
{
    private const MODEL = 'Falconsai/nsfw_image_detection';
    private const API_URL = 'https://router.huggingface.co/hf-inference/models/';

    public function getName(): string
    {
        return 'huggingface';
    }

    public function analyze(string $imageContents): ModerationResult
    {
        $token = config('services.huggingface.token');
        $threshold = config('genpic.content_moderation.thresholds.nudity', 0.60);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])
            ->timeout(30)
            ->connectTimeout(10)
            ->withBody($imageContents, 'application/octet-stream')
            ->post(self::API_URL . self::MODEL);

            if (!$response->successful()) {
                // Modelo cargando (503) o error temporal — fail-open
                Log::warning('HuggingFace NSFW HTTP error — fail-open', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 300),
                ]);
                return ModerationResult::pass($this->getName());
            }

            $data = $response->json();

            // Respuesta: array de [{label: "nsfw", score: 0.95}, {label: "normal", score: 0.05}]
            if (!is_array($data) || empty($data)) {
                Log::warning('HuggingFace NSFW: respuesta inesperada — fail-open', [
                    'body' => substr($response->body(), 0, 300),
                ]);
                return ModerationResult::pass($this->getName());
            }

            $nsfwScore = $this->extractNsfwScore($data);

            if ($nsfwScore >= $threshold) {
                return new ModerationResult(
                    isProhibited: true,
                    violationType: 'nudity',
                    score: $nsfwScore,
                    rawResponse: $data,
                    provider: $this->getName(),
                );
            }

            return ModerationResult::pass($this->getName());

        } catch (\Exception $e) {
            // Fail-open: si el servicio falla, permitir la subida
            Log::warning('HuggingFace NSFW exception — fail-open', [
                'error' => $e->getMessage(),
            ]);
            return ModerationResult::pass($this->getName());
        }
    }

    /**
     * Extraer score de la label "nsfw" de la respuesta del modelo.
     */
    private function extractNsfwScore(array $data): float
    {
        foreach ($data as $item) {
            if (isset($item['label']) && strtolower($item['label']) === 'nsfw') {
                return (float) ($item['score'] ?? 0);
            }
        }
        return 0.0;
    }

    public function isAvailable(): bool
    {
        return !empty(config('services.huggingface.token'));
    }
}
