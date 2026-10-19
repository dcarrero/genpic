<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\GenerationSession;
use App\Services\StorageService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminMediaController extends Controller
{
    /**
     * Sirve en streaming la foto original de una sesión.
     * Protegido por sesión de admin + allowlist de IP — nunca URLs públicas a Wasabi.
     *
     * GET /{admin_path}/media/session/{session:uuid}/original
     */
    public function original(GenerationSession $session): StreamedResponse
    {
        return $this->stream($session->original_photo_path);
    }

    /**
     * Sirve en streaming una imagen generada (raw o watermarked).
     * Protegido por sesión de admin + allowlist de IP — nunca URLs públicas a Wasabi.
     *
     * GET /{admin_path}/media/generation/{generation}/{kind}  — kind: raw|watermarked
     */
    public function generation(Generation $generation, string $kind): StreamedResponse
    {
        abort_unless(in_array($kind, ['raw', 'watermarked'], true), 404);

        $path = $kind === 'raw'
            ? $generation->raw_image_path
            : $generation->watermarked_image_path;

        return $this->stream($path);
    }

    /**
     * Hace streaming del fichero en $path pasando por la app.
     * La URL no sirve sin sesión de admin válida + IP autorizada.
     * No genera URLs firmadas (no filtrables por requisito de negocio).
     */
    private function stream(?string $path): StreamedResponse
    {
        $storage = app(StorageService::class);

        if (! $path || ! $storage->exists($path)) {
            abort(404, 'Archivo no disponible (puede haber sido purgado)');
        }

        $disk = Storage::disk($storage->disk());

        // Mime por extensión — evita una llamada extra a S3 y fallos de detección
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return response()->stream(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if (! $stream) {
                return;
            }
            // Streaming manual: fpassthru() está en disable_functions en el hosting endurecido,
            // por eso NO usamos Storage::response(). stream_copy_to_stream es core y siempre está.
            $out = fopen('php://output', 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => $storage->size($path),
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Disposition' => 'inline',
        ]);
    }
}
