<?php

namespace App\Console\Commands;

use App\Models\IpBan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class SyncIpBansToRedis extends Command
{
    protected $signature = 'moderation:sync-bans';

    protected $description = 'Sincronizar bans activos de BD a Redis (ejecutar tras restart de Redis)';

    public function handle(): int
    {
        $activeBans = IpBan::active()->get();
        $synced = 0;

        foreach ($activeBans as $ban) {
            $ttl = (int) now()->diffInSeconds($ban->expires_at, false);
            if ($ttl > 0) {
                Redis::setex('ip_ban:' . $ban->ip_address, $ttl, '1');
                $synced++;
            }
        }

        $this->info("Sincronizados {$synced} bans activos a Redis (de {$activeBans->count()} en BD).");

        return self::SUCCESS;
    }
}
