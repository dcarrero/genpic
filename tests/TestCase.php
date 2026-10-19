<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sin build de Vite en el checkout de CI — los tests no necesitan assets compilados.
        $this->withoutVite();

        // Hacer Redis fail-open para tests: evita Class "Redis" not found en flujos
        // que usen el facade directamente (PortraitAccessController, MyDownloadsController).
        $this->fakeRedis();
    }

    /**
     * Registra mocks de Redis para los patrones usados en la app:
     *   - pin:{uuid}, pin_attempts:{uuid}, verified:{uuid}:{ip} → PortraitAccessController
     *   - downloads_pin:{email}, downloads_token:{token}      → MyDownloadsController
     *   - ip_ban:{ip}                                         → CheckIpBan (plan 001)
     *
     * Por defecto todos los get() devuelven null (acceso no bloqueado, PIN no presente)
     * y todas las escrituras (setex/del/incr/expire) devuelven true.
     * Los tests individuales pueden sobreescribir con Redis::shouldReceive() adicionales.
     */
    protected function fakeRedis(): void
    {
        Redis::shouldReceive('get')->andReturn(null)->byDefault();
        Redis::shouldReceive('setex')->andReturn(true)->byDefault();
        Redis::shouldReceive('del')->andReturn(1)->byDefault();
        Redis::shouldReceive('incr')->andReturn(1)->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();
        Redis::shouldReceive('ttl')->andReturn(-1)->byDefault();
    }
}
