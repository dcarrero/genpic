<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentViolation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip_address',
        'email',
        'user_id',
        'user_agent',
        'session_id',
        'file_hash',
        'file_name',
        'file_size_bytes',
        'violation_type',
        'violation_score',
        'provider',
        'provider_response',
        'locale',
        'created_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'violation_score'   => 'decimal:4',
        'created_at'        => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ipBan(): HasOne
    {
        return $this->hasOne(IpBan::class, 'violation_id');
    }

    /**
     * Registrar una violacion de contenido (patron ProviderLog::record).
     */
    public static function record(
        string $ip,
        ?string $email,
        ?string $userAgent,
        string $fileHash,
        ?string $fileName,
        ?int $fileSizeBytes,
        string $violationType,
        ?float $violationScore,
        string $provider,
        ?array $providerResponse = null,
        ?string $locale = null,
        ?int $userId = null,
    ): static {
        return static::create([
            'ip_address'        => $ip,
            'email'             => $email,
            'user_id'           => $userId,
            'user_agent'        => $userAgent,
            'session_id'        => session()->getId(),
            'file_hash'         => $fileHash,
            'file_name'         => $fileName,
            'file_size_bytes'   => $fileSizeBytes,
            'violation_type'    => $violationType,
            'violation_score'   => $violationScore,
            'provider'          => $provider,
            'provider_response' => $providerResponse,
            'locale'            => $locale ?? app()->getLocale(),
            'created_at'        => now(),
        ]);
    }
}
