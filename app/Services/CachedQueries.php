<?php

namespace App\Services;

use App\Models\ArtisticStyle;
use App\Models\GalleryExample;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Consultas cacheadas para datos que cambian poco (estilos, productos, galería).
 * TTL 7 días — se invalidan automáticamente via model events al editar en admin.
 */
class CachedQueries
{
    public static function activeStyles()
    {
        return Cache::remember('q:styles:active', 604800, function () {
            return ArtisticStyle::active()->get();
        });
    }

    public static function styleByKey(string $key): ?ArtisticStyle
    {
        return static::activeStyles()->firstWhere('style_key', $key);
    }

    public static function activeProducts()
    {
        return Cache::remember('q:products:active', 604800, function () {
            return Product::active()->with('formats')->get();
        });
    }

    public static function mainProducts()
    {
        return Cache::remember('q:products:main', 604800, function () {
            return Product::mainProducts()->with('formats')->get();
        });
    }

    public static function productByKey(string $key): ?Product
    {
        return Cache::remember("q:product:{$key}", 604800, function () use ($key) {
            return Product::where('product_key', $key)->first();
        });
    }

    public static function galleryExample(int $id): GalleryExample
    {
        return Cache::remember("q:gallery:{$id}", 604800, function () use ($id) {
            return GalleryExample::where('active', true)->findOrFail($id);
        });
    }

    /**
     * Invalida caches cuando se modifican modelos en admin.
     */
    public static function flushStyles(): void
    {
        Cache::forget('q:styles:active');
    }

    public static function flushProducts(): void
    {
        Cache::forget('q:products:active');
        Cache::forget('q:products:main');
        // Flush individual product keys
        Product::pluck('product_key')->each(fn ($k) => Cache::forget("q:product:{$k}"));
    }

    public static function flushGallery(?int $id = null): void
    {
        if ($id) {
            Cache::forget("q:gallery:{$id}");
        }
    }
}
