<?php

namespace App\Console\Commands;

use App\Models\ArtisticStyle;
use App\Models\GalleryExample;
use App\Services\ImageGeneration\ImageGenerationService;
use App\Services\ImageMetadataService;
use App\Services\StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Genera ejemplos para la galería pública.
 *
 * Uso:
 *   1. Sube fotos a storage/app/private/gallery-source/
 *      Nombra los archivos: family_01.jpg, couple_01.jpg, individual_01.jpg, group_01.jpg
 *   2. Ejecuta: php artisan gallery:generate
 *   3. Cada foto se genera con un estilo aleatorio (sin repetir)
 *
 * Opciones:
 *   --photo=family_01.jpg   Generar solo una foto específica
 *   --style=renacimiento    Forzar un estilo en vez de aleatorio
 *   --dry-run               Solo mostrar qué haría, sin generar
 */
class GenerateGalleryExamples extends Command
{
    protected $signature = 'gallery:generate
        {--photo= : Generar solo una foto específica}
        {--style= : Forzar un estilo específico}
        {--dry-run : Solo mostrar, no generar}
        {--featured : Marcar como destacado en landing}';

    protected $description = 'Genera ejemplos para la galería a partir de fotos en gallery-source/';

    private const SOURCE_DIR = 'gallery-source';

    public function handle(ImageGenerationService $service, StorageService $storage): int
    {
        $disk = $storage->disk();

        // Listar fotos en gallery-source/
        $files = Storage::disk($disk)->files(self::SOURCE_DIR);
        $photos = collect($files)->filter(fn ($f) => preg_match('/\.(jpg|jpeg|png|webp)$/i', $f));

        if ($this->option('photo')) {
            $photos = $photos->filter(fn ($f) => str_contains($f, $this->option('photo')));
        }

        if ($photos->isEmpty()) {
            $this->error('No hay fotos en ' . self::SOURCE_DIR . '/');
            $this->line('Sube fotos con formato: family_01.jpg, couple_01.jpg, individual_01.jpg, group_01.jpg');
            return self::FAILURE;
        }

        // Estilos disponibles (sin repetir)
        $usedStyleIds = GalleryExample::pluck('artistic_style_id')->toArray();
        $availableStyles = ArtisticStyle::active()
            ->whereNotIn('id', $usedStyleIds)
            ->get()
            ->shuffle();

        if ($this->option('style')) {
            $forcedStyle = ArtisticStyle::where('style_key', $this->option('style'))->first();
            if (!$forcedStyle) {
                $this->error("Estilo '{$this->option('style')}' no encontrado.");
                return self::FAILURE;
            }
        }

        $this->info("Fotos encontradas: {$photos->count()}");
        $this->info("Estilos disponibles: {$availableStyles->count()}");
        $this->newLine();

        $manager = new ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
        $generated = 0;

        foreach ($photos as $index => $photoPath) {
            $filename = basename($photoPath);
            $photoType = $this->detectPhotoType($filename);

            // Elegir estilo
            if (isset($forcedStyle)) {
                $style = $forcedStyle;
            } else {
                $style = $availableStyles->shift();
                if (!$style) {
                    $this->warn("Sin estilos disponibles. Reciclando...");
                    $availableStyles = ArtisticStyle::active()->get()->shuffle();
                    $style = $availableStyles->shift();
                }
            }

            $this->line(sprintf(
                "  [%d/%d] %s → %s (%s)",
                $index + 1, $photos->count(), $filename, $style->style_key, $photoType
            ));

            if ($this->option('dry-run')) continue;

            try {
                // Leer foto original
                $originalContent = Storage::disk($disk)->get($photoPath);

                // Preparar URL para el proveedor
                $imageRef = $photoPath;
                if ($disk === 'wasabi') {
                    $imageRef = $storage->temporaryUrl($photoPath, now()->addMinutes(15));
                }

                // Generar con IA
                $this->output->write('    Generando... ');
                $result = $service->generate([
                    'image_url'       => $imageRef,
                    'prompt'          => $style->base_prompt,
                    'negative_prompt' => $style->negative_prompt,
                    'style_strength'  => $style->style_strength,
                ]);
                $this->info("OK ({$result['elapsed_ms']}ms, {$result['provider']})");

                // Guardar resultado
                $resultPath = $storage->store($result['image'], 'gallery', $style->style_key, 'jpg');

                // Generar thumbnail 400px
                $thumb = $manager->read($result['image']);
                $thumb->scaleDown(400);
                ImageMetadataService::apply($thumb->core()->native(), $style->label_es, 'Gallery example');
                $thumbPath = "gallery/thumbs/{$style->style_key}_" . basename($resultPath);
                Storage::disk($disk)->put($thumbPath, $thumb->toJpeg(85)->toString());

                // Copiar original a gallery/originals/
                $origGalleryPath = "gallery/originals/{$filename}";
                if (!Storage::disk($disk)->exists($origGalleryPath)) {
                    Storage::disk($disk)->put($origGalleryPath, $originalContent);
                }

                // Crear registro en BD
                $locale = app()->getLocale();
                $styleLabel = $style->t('label');

                GalleryExample::create([
                    'title_es'          => $style->label_es,
                    'title_en'          => $style->label_en,
                    'photo_type'        => $photoType,
                    'artistic_style_id' => $style->id,
                    'style_key'         => $style->style_key,
                    'original_path'     => $origGalleryPath,
                    'result_path'       => $resultPath,
                    'thumbnail_path'    => $thumbPath,
                    'featured'          => (bool) $this->option('featured'),
                    'sort_order'        => $generated,
                    'active'            => true,
                ]);

                $generated++;
                $this->info("    Guardado: {$resultPath}");

            } catch (\Exception $e) {
                $this->error("    Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Generados: {$generated}/{$photos->count()}");

        return self::SUCCESS;
    }

    private function detectPhotoType(string $filename): string
    {
        $lower = strtolower($filename);

        if (str_starts_with($lower, 'family') || str_starts_with($lower, 'familia')) return 'family';
        if (str_starts_with($lower, 'couple') || str_starts_with($lower, 'pareja')) return 'couple';
        if (str_starts_with($lower, 'individual') || str_starts_with($lower, 'retrato') || str_starts_with($lower, 'portrait')) return 'individual';
        if (str_starts_with($lower, 'group') || str_starts_with($lower, 'grupo') || str_starts_with($lower, 'friend') || str_starts_with($lower, 'pet') || str_starts_with($lower, 'mascota')) return 'group';

        return 'family'; // default
    }
}
