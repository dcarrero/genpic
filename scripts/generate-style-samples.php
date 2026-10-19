<?php
/**
 * Script para generar imágenes de muestra en todos los estilos.
 * Ejecutar: docker compose exec app php scripts/generate-style-samples.php
 *
 * Genera imágenes usando fal.ai SeedReam y las guarda en public/img/styles/
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$falKey = config('image_providers.providers.fal_ai.key');
$model = config('image_providers.providers.fal_ai.model');
$baseUrl = config('image_providers.providers.fal_ai.base_url');
$imageUrl = 'https://v3b.fal.media/files/b/0a92ec16/wVI4aRVhEyR1R2OQ4akqW_happy-family-genpic.jpg';

$facePrefix = 'CRITICAL: The faces are the most important part. Keep EXACT facial features, bone structure, skin tone, eye color, hair color and hairstyle of every person — each person must be immediately recognizable as themselves. Do NOT alter, idealize or stylize the faces. The face identity preservation is more important than the style transformation. Transform clothing, shoes, footwear, accessories and background to match the artistic period — replace modern elements with period-appropriate ones. ';

$outputDir = dirname(__DIR__) . '/public/img/styles';
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

$styles = \App\Models\ArtisticStyle::active()->orderBy('sort_order')->get();

echo "Generating samples for {$styles->count()} styles...\n";
echo "Image: {$imageUrl}\n";
echo "Model: {$model}\n\n";

foreach ($styles as $style) {
    $outputFile = "{$outputDir}/{$style->style_key}.jpg";
    $thumbFile = "{$outputDir}/{$style->style_key}_thumb.jpg";

    // Skip si ya existe
    if (file_exists($outputFile)) {
        echo "[SKIP] {$style->style_key} — already exists\n";
        continue;
    }

    $prompt = $facePrefix . 'Apply this style: ' . $style->base_prompt;

    echo "[GEN] {$style->style_key} — {$style->label_es}... ";

    $ch = curl_init("{$baseUrl}/{$model}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
            "Authorization: Key {$falKey}",
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'prompt' => $prompt,
            'image_urls' => [$imageUrl],
        ]),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "FAIL (HTTP {$httpCode})\n";
        continue;
    }

    $data = json_decode($response, true);
    $resultUrl = $data['images'][0]['url'] ?? null;

    if (!$resultUrl) {
        echo "FAIL (no image URL)\n";
        continue;
    }

    // Descargar imagen original
    $imageContent = file_get_contents($resultUrl);
    file_put_contents($outputFile, $imageContent);

    // Crear thumbnail (300px)
    $imagick = new \Imagick();
    $imagick->readImageBlob($imageContent);
    $imagick->scaleImage(300, 0);
    $imagick->setImageCompressionQuality(80);
    $imagick->writeImage($thumbFile);
    $imagick->destroy();

    $size = round(filesize($outputFile) / 1024);
    echo "OK ({$size}KB)\n";

    // Pequeña pausa para no saturar la API
    usleep(500000); // 0.5s
}

echo "\nDone! Check {$outputDir}\n";
