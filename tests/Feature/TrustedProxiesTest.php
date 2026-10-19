<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    /**
     * Un atacante conectado directamente (REMOTE_ADDR fuera de Cloudflare)
     * no puede falsificar su IP enviando X-Forwarded-For.
     * La app debe ignorar el header y devolver REMOTE_ADDR.
     */
    public function test_attacker_cannot_spoof_ip_with_xff(): void
    {
        Route::get('/_test-ip', fn () => request()->ip());

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->withHeaders(['X-Forwarded-For' => '9.9.9.9'])
            ->get('/_test-ip');

        $response->assertStatus(200);
        $this->assertSame('203.0.113.50', $response->getContent());
    }

    /**
     * Una request que llega desde un rango de Cloudflare sí tiene su
     * X-Forwarded-For honrado: la app devuelve la IP real del visitante.
     */
    public function test_cloudflare_xff_is_trusted(): void
    {
        Route::get('/_test-ip-cf', fn () => request()->ip());

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeaders(['X-Forwarded-For' => '81.40.20.10'])
            ->get('/_test-ip-cf');

        $response->assertStatus(200);
        $this->assertSame('81.40.20.10', $response->getContent());
    }
}
