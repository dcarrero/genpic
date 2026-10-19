<?php

namespace App\Services\ImageGeneration\Providers;

use App\Exceptions\ContentPolicyException;
use App\Services\ImageGeneration\Contracts\ImageProviderInterface;
use App\Services\ImageGeneration\PromptBuilder;
use Illuminate\Support\Facades\Http;

/**
 * Novita.ai — SeedReam 4.5 via /v3/seedream-4.5
 * Coste: $0.03/imagen
 * Endpoint: POST https://api.novita.ai/v3/seedream-4.5
 */
class NovitaProvider implements ImageProviderInterface
{

    public function getName(): string
    {
        return 'novita';
    }

    public function generate(array $params): array
    {
        $config = config('image_providers.providers.novita');
        $startTime = microtime(true);

        $prompt = PromptBuilder::build($params['prompt']);

        // Novita: mínimo 3.686.400 px, máximo 16.777.216 px.
        // Respetar aspect ratio original para no recomponer/cortar cabezas.
        $size = $this->calculateSize($params['width'] ?? 2048, $params['height'] ?? 2048);

        $body = [
            'prompt'    => $prompt,
            'size'      => $size,
            'watermark' => false,
        ];

        // Image-to-image: pasar imagen de referencia (array de strings)
        if (!empty($params['image_url'])) {
            if (str_starts_with($params['image_url'], 'http')) {
                $body['image'] = [$params['image_url']];
            } else {
                // Path local: convertir a data URI string para Novita
                $disk = app(\App\Services\StorageService::class)->disk();
                $content = \Illuminate\Support\Facades\Storage::disk($disk)->get($params['image_url']);
                if ($content) {
                    $mime = \Illuminate\Support\Facades\Storage::disk($disk)->mimeType($params['image_url']) ?? 'image/jpeg';
                    $body['image'] = ['data:' . $mime . ';base64,' . base64_encode($content)];
                }
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['key'],
            'Content-Type'  => 'application/json',
        ])
        ->timeout($config['timeout_seconds'] ?? 120)
        ->post("{$config['base_url']}/v3/seedream-4.5", $body);

        if (!$response->successful()) {
            $body = $response->body();
            if ($this->isContentPolicyError($response->status(), $body)) {
                throw new ContentPolicyException($this->getName(), $body);
            }
            throw new \RuntimeException("Novita error: {$response->status()} — {$body}");
        }

        $data = $response->json();
        $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

        // Novita devuelve images[0] como string URL directa
        $firstImage = $data['images'][0] ?? null;
        $imageUrl = is_array($firstImage) ? ($firstImage['url'] ?? null) : $firstImage;

        if (!$imageUrl || !is_string($imageUrl)) {
            throw new \RuntimeException('Novita: no image URL in response — ' . substr(json_encode($data), 0, 300));
        }

        $imageContent = Http::timeout(60)->get($imageUrl)->body();

        return [
            'image'         => $imageContent,
            'provider'      => $this->getName(),
            'model'         => 'seedream-4.5',
            'seed'          => $data['seed'] ?? 0,
            'elapsed_ms'    => $elapsedMs,
            'cost_estimate' => $this->estimateCost($params),
        ];
    }

    public function estimateCost(array $params): float
    {
        return config('image_providers.providers.novita.cost_per_image', 0.030);
    }

    public function isAvailable(): bool
    {
        $config = config('image_providers.providers.novita');
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

    /**
     * Calcula dimensiones que respetan el aspect ratio original
     * dentro de los límites de Novita (min 3.686.400 px, max 16.777.216 px).
     */
    private function calculateSize(int $origWidth, int $origHeight): string
    {
        $ratio = $origWidth / $origHeight;
        $minPixels = 3_686_400;
        $maxPixels = 16_777_216;

        // Partir de 2048 en el lado más largo
        if ($origWidth >= $origHeight) {
            $w = 2048;
            $h = (int) round($w / $ratio);
        } else {
            $h = 2048;
            $w = (int) round($h * $ratio);
        }

        // Asegurar mínimo de píxeles
        $total = $w * $h;
        if ($total < $minPixels) {
            $scale = sqrt($minPixels / $total);
            $w = (int) round($w * $scale);
            $h = (int) round($h * $scale);
        }

        // Asegurar máximo de píxeles
        $total = $w * $h;
        if ($total > $maxPixels) {
            $scale = sqrt($maxPixels / $total);
            $w = (int) round($w * $scale);
            $h = (int) round($h * $scale);
        }

        // Dimensiones pares (requerimiento común en modelos IA)
        $w -= $w % 2;
        $h -= $h % 2;

        return "{$w}x{$h}";
    }
}
