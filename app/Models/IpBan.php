<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBan extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'violation_id',
        'banned_at',
        'expires_at',
        'unbanned_at',
        'unbanned_by',
    ];

    protected $casts = [
        'banned_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'unbanned_at' => 'datetime',
    ];

    public function violation(): BelongsTo
    {
        return $this->belongsTo(ContentViolation::class, 'violation_id');
    }

    /**
     * Bans activos: no expirados y no desbaneados manualmente.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now())
                     ->whereNull('unbanned_at');
    }

    /**
     * Filtrar por IP.
     */
    public function scopeForIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture() && is_null($this->unbanned_at);
    }
}
