<?php

namespace App\Http\Controllers;

use App\Models\GalleryExample;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = GalleryExample::active()->with('artisticStyle');

        // Filtro por tipo si viene en query string
        if ($request->has('type') && in_array($request->type, ['family', 'couple', 'individual', 'group'])) {
            $query->where('photo_type', $request->type);
        }

        $examples = $query->paginate(6)->withQueryString();
        $photoTypes = GalleryExample::photoTypeLabels();
        $title = __('ui.seo_gallery_title');
        $description = __('ui.seo_gallery_description');

        return view('gallery.index', compact('examples', 'photoTypes', 'title', 'description'));
    }

    /**
     * Detalle de un ejemplo — página con slider grande y CTA.
     */
    public function show(int $id)
    {
        $example = GalleryExample::where('active', true)->with('artisticStyle')->findOrFail($id);
        $photoTypes = GalleryExample::photoTypeLabels();

        $locale = app()->getLocale();
        $styleName = $example->artisticStyle->t('label');
        $title = __('ui.seo_gallery_item_title', ['style' => $styleName]);
        $description = __('ui.seo_gallery_item_description', ['style' => $styleName]);

        return view('gallery.show', compact('example', 'photoTypes', 'title', 'description'));
    }

    /**
     * Servir imagen de galería (pública, cacheable).
     * Redimensiona automáticamente para web (max 800px).
     */
    public function image(int $id, string $type, StorageService $storage)
    {
        $example = \App\Services\CachedQueries::galleryExample($id);

        $path = match ($type) {
            'original'  => $example->original_path,
            'result'    => $example->result_path,
            'thumbnail' => $example->thumbnail_path ?? $example->result_path,
            default     => abort(404),
        };

        $disk = $storage->disk();
        if (!Storage::disk($disk)->exists($path)) abort(404);

        // Servir versión web optimizada (cache en disco)
        $cachePath = "gallery/cache/{$id}_{$type}.jpg";

        if (!Storage::disk($disk)->exists($cachePath)) {
            $content = Storage::disk($disk)->get($path);
            $manager = new ImageManager(\Intervention\Image\Drivers\Imagick\Driver::class);
            $image = $manager->read($content);
            $image->scaleDown(800);
            Storage::disk($disk)->put($cachePath, $image->toJpeg(85)->toString());
        }

        $cached = Storage::disk($disk)->get($cachePath);

        return response($cached, 200, [
            'Content-Type'  => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800', // 7 días cache
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
