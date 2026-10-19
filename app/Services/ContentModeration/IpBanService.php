<?php

namespace App\Services\ContentModeration;

use App\Models\IpBan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class IpBanService
{
    private const REDIS_PREFIX = 'ip_ban:';

    /**
     * Verificar si una IP esta baneada — Redis GET O(1).
     */
    public function isBanned(string $ip): bool
    {
        try {
            return (bool) Redis::get(self::REDIS_PREFIX.$ip);
        } catch (\Throwable $e) {
            // Fail-open: si Redis falla, no bloquear
            Log::warning('IpBanService Redis check failed — fail-open', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Banear una IP por X horas.
     * Escribe en Redis (lookups rapidos) + BD (persistencia, admin UI).
     */
    public function ban(string $ip, int $hours, ?int $violationId = null): IpBan
    {
        $expiresAt = now()->addHours($hours);

        // Upsert en BD: si ya existe un ban para esta IP, actualizar con el nuevo
        $ban = IpBan::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => 'content_violation',
                'violation_id' => $violationId,
                'banned_at' => now(),
                'expires_at' => $expiresAt,
                'unbanned_at' => null,  // Resetear si fue desbaneado antes
                'unbanned_by' => null,
            ]
        );

        $this->syncToRedis($ban);

        Log::info('IP baneada por contenido prohibido', [
            'ip' => $ip,
            'hours' => $hours,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return $ban;
    }

    /**
     * Desbanear una IP manualmente (admin).
     */
    public function unban(string $ip, string $adminEmail): void
    {
        $ban = IpBan::where('ip_address', $ip)->first();

        if ($ban) {
            $ban->update([
                'unbanned_at' => now(),
                'unbanned_by' => $adminEmail,
            ]);
        }

        $this->removeFromRedis($ip);

        Log::info('IP desbaneada por admin', [
            'ip' => $ip,
            'admin' => $adminEmail,
        ]);
    }

    private function syncToRedis(IpBan $ban): void
    {
        try {
            // Carbon 3 devuelve diffs con signo; invertir el orden da TTL positivo si expires_at es futuro
            $ttl = (int) now()->diffInSeconds($ban->expires_at, false);
            if ($ttl > 0) {
                Redis::setex(self::REDIS_PREFIX.$ban->ip_address, $ttl, '1');
            }
        } catch (\Throwable $e) {
            Log::warning('IpBanService Redis sync failed', ['error' => $e->getMessage()]);
        }
    }

    private function removeFromRedis(string $ip): void
    {
        try {
            Redis::del(self::REDIS_PREFIX.$ip);
        } catch (\Throwable $e) {
            Log::warning('IpBanService Redis remove failed', ['error' => $e->getMessage()]);
        }
    }
}
