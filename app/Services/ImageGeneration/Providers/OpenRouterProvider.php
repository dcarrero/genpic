<?php

namespace App\Services\ImageGeneration\Providers;

use App\Exceptions\ContentPolicyException;
use App\Services\ImageGeneration\Contracts\ImageProviderInterface;
use App\Services\ImageGeneration\ImageCdnUploader;
use App\Services\ImageGeneration\PromptBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

/**
 * OpenRouter — SeedReam 4.5 via chat/completions con modalities: ["image"]
 * Respuesta: choices[0].message.images[0].image_url.url (base64 data URL)
 * Genera a 4K por defecto con aspect ratio de la imagen original.
 * Coste: $0.04/imagen (igual para 2K y 4K)
 */
class OpenRouterProvider implements ImageProviderInterface
{
    /** Aspect ratios soportados por OpenRouter (mayor a menor). */
    private const SUPPORTED_RATIOS = [
        '21:9'  => 21 / 9,
        '16:9'  => 16 / 9,
        '3:2'   => 3 / 2,
        '4:3'   => 4 / 3,
        '5:4'   => 5 / 4,
        '1:1'   => 1 / 1,
        '4:5'   => 4 / 5,
        '3:4'   => 3 / 4,
        '2:3'   => 2 / 3,
        '9:16'  => 9 / 16,
    ];

    public function getName(): string
    {
        return 'openrouter';
    }

    public function generate(array $params): array
    {
        $config = config('image_providers.providers.openrouter');
        $model = $config['model'] ?? 'bytedance-seed/seedream-4.5';
        $startTime = microtime(true);

        $prompt = PromptBuilder::build($params['prompt']);

        // Construir messages
        $content = [];

        // OpenRouter image-to-image siempre genera cuadrado (2048x2048).
        // Para evitar que recomponga y corte cabezas, pre-procesamos la imagen:
        // padamos a cuadrada con fondo desenfocado → la IA trabaja en cuadrado → recortamos al ratio original.
        $origWidth = $params['width'] ?? 0;
        $origHeight = $params['height'] ?? 0;
        $needsCrop = false;

        if (!empty($params['image_url'])) {
            $imageUrl = $params['image_url'];

            // Si la imagen no es cuadrada, padarla
            if ($origWidth > 0 && $origHeight > 0 && abs($origWidth - $origHeight) > 50) {
                $imageUrl = $this->padToSquare($imageUrl);
                $needsCrop = true;
                Log::info("OpenRouter: imagen padada a cuadrada ({$origWidth}x{$origHeight} → cuadrado)");
            }

            $publicUrl = ImageCdnUploader::toPublicUrl($imageUrl);
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $publicUrl]];
        }

        $content[] = ['type' => 'text', 'text' => $prompt];

        $body = [
            'model'      => $model,
            'messages'   => [['role' => 'user', 'content' => $content]],
            'modalities' => ['image'],
        ];

        // image_config solo funciona en text-to-image (SeedReam no lo soporta con imagen de referencia)
        if (empty($params['image_url'])) {
            $body['image_config'] = [
                'image_size'   => $config['image_size'] ?? '4K',
                'aspect_ratio' => $this->closestAspectRatio($origWidth ?: 1024, $origHeight ?: 1024),
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['key'],
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => config('app.name'),
        ])
        ->timeout($config['timeout_seconds'] ?? 120)
        ->post("{$config['base_url']}/chat/completions", $body);

        if (!$response->successful()) {
            $body = $response->body();
            if ($this->isContentPolicyError($response->status(), $body)) {
                throw new ContentPolicyException($this->getName(), $body);
            }
            throw new \RuntimeException("OpenRouter error: {$response->status()} — {$body}");
        }

        $data = $response->json();
        $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

        // Respuesta: choices[0].message.images[0].image_url.url
        $imageUrl = $data['choices'][0]['message']['images'][0]['image_url']['url'] ?? null;

        if (!$imageUrl) {
            throw new \RuntimeException('OpenRouter: no image in response — ' . substr(json_encode($data), 0, 300));
        }

        // Decodificar base64 data URL o descargar HTTP URL
        if (str_starts_with($imageUrl, 'data:')) {
            $imageContent = base64_decode(explode(',', $imageUrl, 2)[1]);
        } elseif (str_starts_with($imageUrl, 'http')) {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();
        } else {
            $imageContent = base64_decode($imageUrl);
        }

        // Post-procesar: recortar al aspect ratio original si se padó
        if ($needsCrop && $origWidth > 0 && $origHeight > 0) {
            $imageContent = $this->cropToOriginalRatio($imageContent, $origWidth, $origHeight);
        }

        return [
            'image'         => $imageContent,
            'provider'      => $this->getName(),
            'model'         => $model,
            'seed'          => 0,
            'elapsed_ms'    => $elapsedMs,
            'cost_estimate' => $this->estimateCost($params),
        ];
    }

    public function estimateCost(array $params): float
    {
        return config('image_providers.providers.openrouter.cost_per_image', 0.040);
    }

    public function isAvailable(): bool
    {
        $config = config('image_providers.providers.openrouter');
        return ($config['enabled'] ?? false) && !empty($config['key']);
    }

    /**
     * Pada la imagen a cuadrada con fondo desenfocado.
     * Evita que OpenRouter recomponga y corte cabezas en imágenes landscape/portrait.
     * Devuelve el path local del archivo padado.
     */
    private function padToSquare(string $imagePath): string
    {
        $disk = app(\App\Services\StorageService::class)->disk();

        // Si es una URL (signed URL de Wasabi), descargar via HTTP
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            $content = file_get_contents($imagePath);
            if ($content === false) {
                throw new \RuntimeException("padToSquare: no se pudo descargar {$imagePath}");
            }
        } else {
            $content = \Illuminate\Support\Facades\Storage::disk($disk)->get($imagePath);
        }

        $manager = new ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
        $image = $manager->read($content);

        $w = $image->width();
        $h = $image->height();
        $side = max($w, $h);

        // Crear fondo: imagen escalada a cuadrado + desenfocada
        $bg = $manager->read($content);
        $bg->cover($side, $side);
        $bgImagick = $bg->core()->native();
        $bgImagick->blurImage(40, 20);
        $bgImagick->modulateImage(60, 60, 100); // oscurecer + desaturar

        // Centrar imagen original sobre fondo
        $offsetX = (int) (($side - $w) / 2);
        $offsetY = (int) (($side - $h) / 2);
        $bg->place($image, 'top-left', $offsetX, $offsetY);

        // Guardar temporalmente
        $paddedPath = "temp/padded_" . md5($imagePath) . '.jpg';
        \Illuminate\Support\Facades\Storage::disk($disk)->put($paddedPath, $bg->toJpeg(90)->toString());

        return $paddedPath;
    }

    /**
     * Recorta el resultado cuadrado al aspect ratio original.
     * Centra el crop para preservar la composición.
     */
    private function cropToOriginalRatio(string $imageContent, int $origWidth, int $origHeight): string
    {
        $manager = new ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
        $image = $manager->read($imageContent);

        $outW = $image->width();
        $outH = $image->height();
        $origRatio = $origWidth / $origHeight;

        if ($origWidth > $origHeight) {
            // Landscape: recortar arriba y abajo
            $cropW = $outW;
            $cropH = (int) round($outW / $origRatio);
            $offsetX = 0;
            $offsetY = (int) (($outH - $cropH) / 2);
        } else {
            // Portrait: recortar lados
            $cropH = $outH;
            $cropW = (int) round($outH * $origRatio);
            $offsetX = (int) (($outW - $cropW) / 2);
            $offsetY = 0;
        }

        $image->crop($cropW, $cropH, $offsetX, $offsetY);

        return $image->toPng()->toString();
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
     * Encuentra el aspect ratio soportado más cercano al de la imagen original.
     */
    private function closestAspectRatio(int $width, int $height): string
    {
        $srcRatio = $width / $height;
        $bestKey = '1:1';
        $bestDiff = PHP_FLOAT_MAX;

        foreach (self::SUPPORTED_RATIOS as $key => $ratio) {
            $diff = abs($srcRatio - $ratio);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $bestKey = $key;
            }
        }

        return $bestKey;
    }
}
