<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PortraitAccessController;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Sirve imágenes de forma segura.
 *
 * Protecciones:
 * 1. PIN verificado (Redis) para previews
 * 2. Order pagada para descargas
 * 3. Referer check — solo desde el propio dominio (APP_URL)
 * 4. Rate limit — max 30 peticiones/minuto por IP
 * 5. Anti-hotlink — headers que impiden embeber en otros sitios
 * 6. Session expirada — no servir si TTL pasado
 * 7. Logging de cada acceso
 */
class SecureImageController extends Controller
{
    /**
     * Servir imagen watermarked.
     */
    /**
     * Servir foto original del usuario (reducida, para slider before/after).
     */
    public function original(Request $request, GenerationSession $session, StorageService $storage)
    {
        if ($session->isExpired()) abort(410);
        if (!$session->original_photo_path) abort(404);

        // Verificación PIN (misma protección que watermarked)
        if (config('genpic.require_pin') && !PortraitAccessController::isVerified($session, $request)) {
            abort(403);
        }

        $this->checkRateLimit($request);
        $this->checkReferer($request);

        // Servir versión reducida (no la original HD)
        $disk = $storage->disk();
        $content = Storage::disk($disk)->get($session->original_photo_path);
        $mime = Storage::disk($disk)->mimeType($session->original_photo_path) ?? 'image/jpeg';

        // Reducir a 600px para preview
        $manager = new \Intervention\Image\ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
        $image = $manager->read($content);
        $image->scaleDown(600);

        return response($image->toJpeg(80)->toString(), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function watermarked(Request $request, GenerationSession $session, StorageService $storage)
    {
        // 1. Sesión expirada
        if ($session->isExpired()) {
            abort(410);
        }

        // 2. PIN verificado (configurable via REQUIRE_PIN_VERIFICATION)
        if (config('genpic.require_pin') && !PortraitAccessController::isVerified($session, $request)) {
            abort(403);
        }

        // 3. Rate limit por IP
        $this->checkRateLimit($request);

        // 4. Referer check
        $this->checkReferer($request);

        $generation = $session->latestGeneration;
        if (!$generation || !$generation->watermarked_image_path) {
            abort(404);
        }

        Log::info('Secure image served', [
            'type'    => 'watermarked',
            'session' => $session->uuid,
            'ip'      => $request->ip(),
        ]);

        return $this->serveFile($storage, $generation->watermarked_image_path);
    }

    /**
     * Servir imagen de exportación (pagada).
     */
    public function export(Request $request, string $orderUuid, string $format, StorageService $storage)
    {
        $order = Order::where('uuid', $orderUuid)->firstOrFail();

        // 1. Order pagada
        if (!$order->isPaid()) {
            abort(403);
        }

        // 2. Sesión no expirada (para archivos)
        if ($order->session->purged_at) {
            abort(410);
        }

        // 3. Verificar acceso (configurable)
        if (config('genpic.require_pin') && !PortraitAccessController::isVerified($order->session, $request)) {
            abort(403);
        }

        // 4. Rate limit
        $this->checkRateLimit($request);

        // 5. Referer
        $this->checkReferer($request);

        $item = $order->items()->whereNotNull('export_paths')->first();
        if (!$item || !isset($item->export_paths[$format])) {
            abort(404);
        }

        Log::info('Secure download served', [
            'type'   => 'export',
            'order'  => $order->uuid,
            'format' => $format,
            'ip'     => $request->ip(),
        ]);

        return $this->serveFile($storage, $item->export_paths[$format], asDownload: true);
    }

    /**
     * Descargar ZIP con todos los formatos del order.
     */
    public function downloadZip(Request $request, string $orderUuid, StorageService $storage)
    {
        $order = Order::where('uuid', $orderUuid)->firstOrFail();

        if (!$order->isPaid()) abort(403);
        if ($order->session->purged_at) abort(410);

        // PIN verification (configurable)
        if (config('genpic.require_pin') && !PortraitAccessController::isVerified($order->session, $request)) {
            abort(403);
        }

        $this->checkRateLimit($request);
        $this->checkReferer($request);

        $item = $order->items()->whereNotNull('export_paths')->first();
        if (!$item || empty($item->export_paths)) abort(404);

        $disk = $storage->disk();
        $zipName = "genpic_com_{$order->uuid}.zip";
        $zipPath = tempnam(sys_get_temp_dir(), 'genpic_') . '.zip';

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($item->export_paths as $formatKey => $path) {
            if (Storage::disk($disk)->exists($path)) {
                $content = Storage::disk($disk)->get($path);
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $zip->addFromString("{$formatKey}_genpic_com.{$ext}", $content);
            }
        }

        $zip->close();

        Log::info('Secure ZIP download', ['order' => $order->uuid, 'ip' => $request->ip()]);

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    /**
     * Rate limit: max 30 imágenes/minuto por IP.
     */
    private function checkRateLimit(Request $request): void
    {
        $key = 'secure_image:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Too many requests');
        }

        RateLimiter::hit($key, 60);
    }

    /**
     * Referer check: rechazar si viene de otro dominio.
     * Permite acceso directo (sin referer) para emails.
     */
    private function checkReferer(Request $request): void
    {
        $referer = $request->header('Referer');

        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            if ($refererHost && $refererHost !== $appHost && $refererHost !== 'localhost') {
                Log::warning('Secure image blocked: invalid referer', [
                    'referer' => $referer,
                    'ip'      => $request->ip(),
                ]);
                abort(403);
            }
        }
    }

    /**
     * Servir archivo con headers anti-hotlink.
     */
    private function serveFile(StorageService $storage, string $path, bool $asDownload = false)
    {
        $disk = $storage->disk();

        if (!Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $content = Storage::disk($disk)->get($path);
        $mime = Storage::disk($disk)->mimeType($path) ?? 'image/jpeg';
        $filename = basename($path);

        $headers = [
            'Content-Type' => $mime,
            // Anti-cache: no guardar en proxy/CDN
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            // Anti-hotlink: no embeber en otros sitios
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Content-Security-Policy' => "default-src 'none'",
            // No indexar
            'X-Robots-Tag' => 'noindex, nofollow',
        ];

        if ($asDownload) {
            $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
        } else {
            // Inline pero no cacheable
            $headers['Content-Disposition'] = 'inline';
        }

        return response($content, 200, $headers);
    }
}
