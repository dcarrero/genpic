<?php

namespace App\Services\ImageGeneration\Providers;

use App\Exceptions\ContentPolicyException;
use App\Services\ImageGeneration\Contracts\ImageProviderInterface;
use App\Services\ImageGeneration\ImageCdnUploader;
use App\Services\ImageGeneration\PromptBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Evolink.ai — SeedReam 4.5 via API REST async.
 * Flujo: POST /v1/images/generations → task_id → poll /v1/tasks/{id} → results[0]
 * Coste: $0.031/imagen
 */
class EvolinkProvider implements ImageProviderInterface
{
    public function getName(): string
    {
        return 'evolink';
    }

    public function generate(array $params): array
    {
        $config = config('image_providers.providers.evolink');
        $startTime = microtime(true);

        $prompt = PromptBuilder::build($params['prompt']);

        $body = [
            'model'  => $config['model'] ?? 'doubao-seedream-4.5',
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => 'auto',
        ];

        // Image-to-image: Evolink requiere URL HTTP real (no base64)
        if (!empty($params['image_url'])) {
            $publicUrl = ImageCdnUploader::toPublicUrl($params['image_url']);
            $body['image_urls'] = [$publicUrl];
        }

        // 1. Crear tarea
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['key'],
            'Content-Type'  => 'application/json',
        ])
        ->timeout(30)
        ->post("{$config['base_url']}/v1/images/generations", $body);

        if (!$response->successful()) {
            $body = $response->body();
            if ($this->isContentPolicyError($response->status(), $body)) {
                throw new ContentPolicyException($this->getName(), $body);
            }
            throw new \RuntimeException("Evolink error: {$response->status()} — {$body}");
        }

        $taskId = $response->json('id');
        if (!$taskId) {
            throw new \RuntimeException('Evolink: no task_id in response');
        }

        // 2. Poll hasta completado
        $imageUrl = $this->pollForResult($taskId, $config);
        $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

        $imageContent = Http::timeout(60)->get($imageUrl)->body();

        return [
            'image'         => $imageContent,
            'provider'      => $this->getName(),
            'model'         => $config['model'] ?? 'doubao-seedream-4.5',
            'seed'          => 0,
            'elapsed_ms'    => $elapsedMs,
            'cost_estimate' => $this->estimateCost($params),
        ];
    }

    private function pollForResult(string $taskId, array $config): string
    {
        $maxWait = $config['timeout_seconds'] ?? 120;
        $start = time();
        $interval = 3;

        while ((time() - $start) < $maxWait) {
            sleep($interval);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['key'],
            ])
            ->timeout(15)
            ->get("{$config['base_url']}/v1/tasks/{$taskId}");

            if (!$response->successful()) continue;

            $data = $response->json();
            $status = $data['status'] ?? 'unknown';

            if ($status === 'completed') {
                // Evolink devuelve results[0] como string URL directa
                $url = $data['results'][0] ?? null;

                if ($url && is_string($url)) return $url;
                throw new \RuntimeException("Evolink: task completed but no image URL");
            }

            if ($status === 'failed') {
                $errorMsg = json_encode($data['error'] ?? 'unknown');
                if ($this->isContentPolicyError(400, $errorMsg)) {
                    throw new ContentPolicyException($this->getName(), $errorMsg);
                }
                throw new \RuntimeException("Evolink: task failed — " . $errorMsg);
            }
        }

        throw new \RuntimeException("Evolink: timeout waiting for task {$taskId}");
    }

    public function estimateCost(array $params): float
    {
        return config('image_providers.providers.evolink.cost_per_image', 0.031);
    }

    public function isAvailable(): bool
    {
        $config = config('image_providers.providers.evolink');
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
