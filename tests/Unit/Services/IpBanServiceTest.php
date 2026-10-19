<?php

namespace Tests\Unit\Services;

use App\Models\IpBan;
use App\Services\ContentModeration\IpBanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IpBanServiceTest extends TestCase
{
    use RefreshDatabase;

    private IpBanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IpBanService;
    }

    #[Test]
    public function ban_escribe_en_redis_con_ttl_positivo(): void
    {
        // Verifica que el bug del TTL negativo (Carbon 3) queda corregido:
        // setex debe recibir un TTL > 0 cuando expires_at es futuro.
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function (string $key, int $ttl, string $valor) {
                return $key === 'ip_ban:1.2.3.4'
                    && $ttl > 0
                    && $ttl <= 72 * 3600 + 5
                    && $valor === '1';
            });

        $ban = $this->service->ban('1.2.3.4', 72);

        $this->assertInstanceOf(IpBan::class, $ban);
        $this->assertDatabaseHas('ip_bans', ['ip_address' => '1.2.3.4']);
        $this->assertTrue($ban->expires_at->isFuture());
        $this->assertEqualsWithDelta(
            now()->addHours(72)->timestamp,
            $ban->expires_at->timestamp,
            10
        );
    }

    #[Test]
    public function is_banned_devuelve_true_cuando_redis_tiene_la_clave(): void
    {
        Redis::shouldReceive('get')
            ->once()
            ->with('ip_ban:5.5.5.5')
            ->andReturn('1');

        $this->assertTrue($this->service->isBanned('5.5.5.5'));
    }

    #[Test]
    public function is_banned_devuelve_false_cuando_redis_no_tiene_la_clave(): void
    {
        Redis::shouldReceive('get')
            ->once()
            ->with('ip_ban:6.6.6.6')
            ->andReturn(null);

        $this->assertFalse($this->service->isBanned('6.6.6.6'));
    }

    #[Test]
    public function is_banned_fail_open_ante_error_de_extension(): void
    {
        // Simula que la extensión phpredis no está cargada — es un \Error, no \Exception.
        // El método debe degradar devolviendo false (fail-open) sin propagar el error.
        Redis::shouldReceive('get')
            ->once()
            ->andThrow(new \Error('Class "Redis" not found'));

        $this->assertFalse($this->service->isBanned('7.7.7.7'));
    }

    #[Test]
    public function unban_elimina_la_clave_de_redis_y_actualiza_bd(): void
    {
        // Preparar un ban en BD
        IpBan::create([
            'ip_address' => '9.9.9.9',
            'reason' => 'content_violation',
            'violation_id' => null,
            'banned_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        Redis::shouldReceive('del')
            ->once()
            ->with('ip_ban:9.9.9.9');

        $this->service->unban('9.9.9.9', 'admin@example.com');

        $ban = IpBan::where('ip_address', '9.9.9.9')->first();
        $this->assertNotNull($ban->unbanned_at);
        $this->assertEquals('admin@example.com', $ban->unbanned_by);
    }
}
