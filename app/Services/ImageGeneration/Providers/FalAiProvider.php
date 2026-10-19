<?php

namespace App\Services\ImageGeneration\Providers;

use App\Exceptions\ContentPolicyException;
use App\Services\ImageGeneration\Contracts\ImageProviderInterface;
use App\Services\ImageGeneration\PromptBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FalAiProvider implements ImageProviderInterface
{

    public function getName(): string
    {
        return 'fal_ai';
    }

    public function generate(array $params): array
    {
        $config = config('image_providers.providers.fal_ai');
        $model = $config['model'] ?? 'fal-ai/bytedance/seedream/v5/lite/edit';
        $startTime = microtime(true);

        // Si image_url es un path local, subir a fal.ai CDN primero
        $imageUrl = $params['image_url'];
        if (!str_starts_with($imageUrl, 'http')) {
            $imageUrl = $this->uploadToFalCdn($imageUrl, $config['key']);
        }

        $prompt = PromptBuilder::build($params['prompt']);

        // Construir payload con todos los parámetros disponibles
        $payload = [
            'prompt'     => $prompt,
            'image_urls' => [$imageUrl],
        ];

        // Negative prompt — crítico para evitar zoom/reframe
        if (!empty($params['negative_prompt'])) {
            $payload['negative_prompt'] = $params['negative_prompt'];
        }

        // Edit strength — controla cuánto cambia la imagen (0.0-1.0)
        // Menor = más fiel al original (menos zoom/reframe)
        if (isset($params['style_strength'])) {
            $payload['edit_strength'] = (float) $params['style_strength'];
        }

        // Tamaño de imagen — mantener dimensiones originales para evitar reframe
        if (!empty($params['width']) && !empty($params['height'])) {
            $payload['image_size'] = [
                'width'  => (int) $params['width'],
                'height' => (int) $params['height'],
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Key ' . $config['key'],
            'Content-Type'  => 'application/json',
        ])
        ->timeout($config['timeout_seconds'])
        ->post("{$config['base_url']}/{$model}", $payload);

        if (!$response->successful()) {
            $body = $response->body();
            if ($this->isContentPolicyError($response->status(), $body)) {
                throw new ContentPolicyException($this->getName(), $body);
            }
            throw new \RuntimeException("fal.ai error: {$response->status()} — {$body}");
        }

        $data = $response->json();
        $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

        $resultImageUrl = $data['images'][0]['url'] ?? null;
        if (!$resultImageUrl) {
            throw new \RuntimeException('fal.ai: no image URL in response');
        }

        $imageContent = Http::timeout(60)->get($resultImageUrl)->body();

        return [
            'image'         => $imageContent,
            'provider'      => $this->getName(),
            'model'         => $model,
            'seed'          => $data['seed'] ?? 0,
            'elapsed_ms'    => $elapsedMs,
            'cost_estimate' => $this->estimateCost($params),
        ];
    }

    /**
     * Subir imagen local al CDN de fal.ai.
     */
    private function uploadToFalCdn(string $localPath, string $apiKey): string
    {
        $disk = app(\App\Services\StorageService::class)->disk();
        $content = Storage::disk($disk)->get($localPath);
        $mime = Storage::disk($disk)->mimeType($localPath) ?? 'image/jpeg';
        $filename = basename($localPath);

        $initResponse = Http::withHeaders([
            'Authorization' => 'Key ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(15)
        ->post('https://rest.alpha.fal.ai/storage/upload/initiate', [
            'content_type' => $mime,
            'file_name'    => $filename,
        ]);

        if (!$initResponse->successful()) {
            throw new \RuntimeException("fal.ai upload initiate failed: {$initResponse->status()}");
        }

        $uploadUrl = $initResponse->json('upload_url');
        $fileUrl = $initResponse->json('file_url');

        Http::withHeaders(['Content-Type' => $mime])
            ->timeout(60)
            ->withBody($content, $mime)
            ->put($uploadUrl);

        return $fileUrl;
    }

    public function estimateCost(array $params): float
    {
        return config('image_providers.providers.fal_ai.cost_per_image', 0.014);
    }

    public function isAvailable(): bool
    {
        $config = config('image_providers.providers.fal_ai');
        return ($config['enabled'] ?? false) && !empty($config['key']);
    }

    private function isContentPolicyError(int $status, string $body): bool
    {
        if (!in_array($status, [400, 403, 422, 451])) {
            return false;
        }
        $lower = strtolower($body);
        $keywords = ['nsfw', 'safety', 'content policy', 'moderation', 'inappropriate', 'explicit', 'nudity', 'nude', 'prohibited', 'policy violation', 'content_policy', 'unsafe'];
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }
}
