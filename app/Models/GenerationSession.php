<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class GenerationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'style_key', 'artistic_style_id',
        'original_photo_path', 'original_filename', 'original_size_bytes', 'face_detected',
        'status', 'email_captured_at', 'session_email_sent_at',
        'email_recovery_sent_at', 'expiry_warning_sent_at',
        'abandonment_email_count', 'abandonment_last_sent_at',
        'upgrade_email_count', 'upgrade_last_sent_at',
        'expires_at', 'purge_scheduled_at', 'purged_at',
        'ab_variant', 'utm_source', 'utm_medium', 'utm_campaign', 'referrer',
        'ip_address', 'locale',
    ];

    protected function casts(): array
    {
        return [
            'face_detected'          => 'boolean',
            'email_captured_at'      => 'datetime',
            'session_email_sent_at'  => 'datetime',
            'email_recovery_sent_at' => 'datetime',
            'expiry_warning_sent_at' => 'datetime',
            'abandonment_last_sent_at' => 'datetime',
            'upgrade_last_sent_at'     => 'datetime',
            'expires_at'             => 'datetime',
            'purge_scheduled_at'     => 'datetime',
            'purged_at'              => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $session) {
            $session->uuid ??= Str::uuid()->toString();
            $session->expires_at ??= now()->addDays((int) config('genpic.session_ttl_days', 14));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artisticStyle(): BelongsTo
    {
        return $this->belongsTo(ArtisticStyle::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function purgeQueue(): HasOne
    {
        return $this->hasOne(PurgeQueue::class);
    }

    public function latestGeneration(): HasOne
    {
        return $this->hasOne(Generation::class)->latestOfMany();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
