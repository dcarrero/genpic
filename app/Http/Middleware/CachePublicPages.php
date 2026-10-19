<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cachea respuestas HTML completas de páginas públicas.
 * TTL configurable via CACHE_PUBLIC_PAGES_HOURS en .env.
 * Cada recarga tras expiración regenera testimonios aleatorios, galería, etc.
 *
 * Excluye: POST, usuarios autenticados, admin, APIs, webhooks.
 */
class CachePublicPages
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo cachear GET de páginas HTML
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // No cachear si hay usuario autenticado (admin)
        if ($request->user()) {
            return $next($request);
        }

        // No cachear si desactivado
        $ttlHours = (int) config('genpic.cache_public_pages_hours', 168); // 7 días por defecto
        if ($ttlHours <= 0) {
            return $next($request);
        }

        $cacheKey = 'page_cache:' . md5($request->fullUrl());

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response($cached['content'], 200, [
                'Content-Type' => $cached['content_type'] ?? 'text/html',
                'X-Cache' => 'HIT',
            ]);
        }

        $response = $next($request);

        // Solo cachear respuestas 200 HTML
        if ($response->getStatusCode() === 200 && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            Cache::put($cacheKey, [
                'content'      => $response->getContent(),
                'content_type' => $response->headers->get('Content-Type'),
            ], now()->addHours($ttlHours));
        }

        $response->headers->set('X-Cache', 'MISS');

        return $response;
    }
}
