<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Sube imágenes locales al CDN de fal.ai para obtener una URL pública temporal.
 * Utilizado por proveedores que necesitan URLs HTTP (Evolink, OpenRouter)
 * cuando no hay Wasabi configurado.
 */
class ImageCdnUploader
{
    /**
     * Convierte un path local a una URL HTTP accesible.
     * - Si ya es HTTP, devuelve tal cual.
     * - Si es local y hay Wasabi, usa signed URL.
     * - Si es local sin Wasabi, sube a fal.ai CDN.
     */
    public static function toPublicUrl(string $imageRef): string
    {
        if (str_starts_with($imageRef, 'http')) {
            return $imageRef;
        }

        $storage = app(\App\Services\StorageService::class);

        // En producción con Wasabi, usar signed URL
        if ($storage->disk() === 'wasabi') {
            return $storage->temporaryUrl($imageRef, now()->addMinutes(15));
        }

        // En local, subir al CDN de fal.ai para obtener URL temporal
        return self::uploadToFalCdn($imageRef);
    }

    private static function uploadToFalCdn(string $localPath): string
    {
        $falKey = config('image_providers.providers.fal_ai.key');
        if (!$falKey) {
            throw new \RuntimeException('fal.ai key required for CDN upload in local environment');
        }

        $disk = app(\App\Services\StorageService::class)->disk();
        $content = Storage::disk($disk)->get($localPath);
        $mime = Storage::disk($disk)->mimeType($localPath) ?? 'image/jpeg';

        $initResponse = Http::withHeaders([
            'Authorization' => 'Key ' . $falKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout(15)
        ->post('https://rest.alpha.fal.ai/storage/upload/initiate', [
            'content_type' => $mime,
            'file_name'    => basename($localPath),
        ]);

        if (!$initResponse->successful()) {
            throw new \RuntimeException("CDN upload failed: {$initResponse->status()}");
        }

        Http::withHeaders(['Content-Type' => $mime])
            ->timeout(60)
            ->withBody($content, $mime)
            ->put($initResponse->json('upload_url'));

        return $initResponse->json('file_url');
    }
}
