<?php

namespace Tests\Unit\Middleware;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class GenerationRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('generation:127.0.0.1');
    }

    /**
     * Caso 1: con el contador al límite (3 hits previos) el middleware devuelve 429.
     */
    public function test_middleware_returns_429_when_rate_limit_exhausted(): void
    {
        $key = 'generation:127.0.0.1';
        $decay = 24 * 3600;

        RateLimiter::hit($key, $decay);
        RateLimiter::hit($key, $decay);
        RateLimiter::hit($key, $decay);

        $response = $this->postJson('/create/upload');

        $response->assertStatus(429);
        $response->assertJsonStructure(['error', 'retry_after']);
    }

    /**
     * Caso 2: sin intentos previos la petición pasa el middleware (no 429).
     * El controller puede devolver 422 de validación — lo que importa es que no sea 429.
     */
    public function test_middleware_allows_request_with_no_prior_attempts(): void
    {
        $response = $this->postJson('/create/upload');

        $this->assertNotEquals(429, $response->status(), 'El middleware no debería bloquear una IP sin intentos previos');
    }

    /**
     * Caso 3 (cross-check de unificación): 3 hits en la clave de PhotoUploader
     * ('generation:{ip}') también bloquean el middleware — confirma que comparten contador.
     */
    public function test_middleware_blocked_by_photouploader_key(): void
    {
        $key = 'generation:127.0.0.1';
        $decay = 24 * 3600;

        // Simulamos los 3 hits que PhotoUploader haría tras sus generaciones exitosas
        RateLimiter::hit($key, $decay);
        RateLimiter::hit($key, $decay);
        RateLimiter::hit($key, $decay);

        $response = $this->postJson('/create/upload');

        $response->assertStatus(429);
    }
}
