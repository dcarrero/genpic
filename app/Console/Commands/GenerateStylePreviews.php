<?php

namespace App\Console\Commands;

use App\Models\ArtisticStyle;
use App\Services\ImageGeneration\ImageGenerationService;
use App\Services\ImageGeneration\PromptBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;

class GenerateStylePreviews extends Command
{
    protected $signature = 'styles:generate-previews
                            {photo : Path to the source photo (local file)}
                            {--style= : Generate only for this style_key}
                            {--category= : Generate only for this category (professional, thematic, adventure)}
                            {--output=public/img/styles : Output directory for thumbnails}';

    protected $description = 'Generate style preview thumbnails using the AI provider';

    public function handle(ImageGenerationService $service): int
    {
        $photoPath = $this->argument('photo');
        if (!file_exists($photoPath)) {
            $this->error("Photo not found: {$photoPath}");
            return 1;
        }

        $outputDir = base_path($this->option('output'));
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $query = ArtisticStyle::where('active', true);

        if ($styleKey = $this->option('style')) {
            $query->where('style_key', $styleKey);
        } elseif ($category = $this->option('category')) {
            $query->where('style_category', $category);
        }

        $styles = $query->orderBy('sort_order')->get();

        if ($styles->isEmpty()) {
            $this->warn('No styles found matching criteria.');
            return 0;
        }

        $this->info("Generating previews for {$styles->count()} styles...");
        $this->newLine();

        $photoContent = file_get_contents($photoPath);
        $imageInfo = getimagesizefromstring($photoContent);
        $origWidth = $imageInfo[0] ?? 1024;
        $origHeight = $imageInfo[1] ?? 1024;

        // Upload photo to fal.ai CDN once — reuse URL for all styles
        $this->info("Uploading source photo to fal.ai CDN...");
        $imageUrl = $this->uploadToFalCdn($photoContent);
        if (!$imageUrl) {
            $this->error("Failed to upload photo to fal.ai CDN");
            return 1;
        }
        $this->info("  Uploaded: {$imageUrl}");
        $this->newLine();

        $manager = new ImageManager(
            \Intervention\Image\Drivers\Imagick\Driver::class
        );

        $succeeded = 0;
        $failed = 0;

        foreach ($styles as $style) {
            try {
                $this->info("  [{$style->style_key}] {$style->t('label', 'es')}...");

                $result = $service->generate([
                    'image_url'       => $imageUrl,
                    'prompt'          => $style->base_prompt,
                    'negative_prompt' => PromptBuilder::negativePrompt($style->negative_prompt ?? ''),
                    'style_strength'  => $style->style_strength,
                    'width'           => $origWidth,
                    'height'          => $origHeight,
                ]);

                // Guardar imagen completa
                $fullPath = "{$outputDir}/{$style->style_key}.jpg";
                $fullImage = $manager->read($result['image']);
                file_put_contents($fullPath, $fullImage->toJpeg(90)->toString());

                // Generar thumbnail 300x300
                $thumb = $manager->read($result['image']);
                $thumb->cover(300, 300);
                $thumbPath = "{$outputDir}/{$style->style_key}_thumb.jpg";
                file_put_contents($thumbPath, $thumb->toJpeg(85)->toString());

                // WebP thumbnail
                $webpPath = "{$outputDir}/{$style->style_key}_thumb.webp";
                $imagick = new \Imagick();
                $imagick->readImageBlob(file_get_contents($thumbPath));
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(80);
                $imagick->writeImage($webpPath);
                $imagick->destroy();

                $this->info("    ✓ OK ({$result['elapsed_ms']}ms, \${$result['cost_estimate']})");
                $succeeded++;

            } catch (\Exception $e) {
                $this->error("    ✗ Failed: {$e->getMessage()}");
                $failed++;
            }

            // Pausa breve entre requests para no saturar
            if ($styles->count() > 1) {
                sleep(2);
            }
        }

        $this->newLine();
        $this->info("Done: {$succeeded} succeeded, {$failed} failed");
        $this->info("Output: {$outputDir}");

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Upload photo directly to fal.ai CDN and return the public URL.
     */
    private function uploadToFalCdn(string $content): ?string
    {
        $falKey = config('image_providers.providers.fal_ai.key');

        $initResponse = Http::withHeaders([
            'Authorization' => 'Key ' . $falKey,
            'Content-Type' => 'application/json',
        ])->timeout(15)->post('https://rest.alpha.fal.ai/storage/upload/initiate', [
            'content_type' => 'image/jpeg',
            'file_name'    => 'style_preview_source.jpg',
        ]);

        if (!$initResponse->successful()) {
            return null;
        }

        $uploadUrl = $initResponse->json('upload_url');
        $fileUrl = $initResponse->json('file_url');

        Http::withHeaders(['Content-Type' => 'image/jpeg'])
            ->timeout(60)
            ->withBody($content, 'image/jpeg')
            ->put($uploadUrl);

        return $fileUrl;
    }
}
