<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Generation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'generation_session_id', 'artistic_style_id',
        'status', 'provider', 'provider_job_id', 'provider_model', 'cost_usd',
        'raw_image_path', 'watermarked_image_path', 'raw_image_size_bytes',
        'prompt_used', 'negative_prompt_used', 'style_strength_used', 'seed',
        'queued_at', 'processing_started_at', 'completed_at', 'elapsed_ms',
        'error_message', 'attempt_number',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd'              => 'decimal:4',
            'style_strength_used'   => 'decimal:2',
            'queued_at'             => 'datetime',
            'processing_started_at' => 'datetime',
            'completed_at'          => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $generation) {
            $generation->uuid ??= Str::uuid()->toString();
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GenerationSession::class, 'generation_session_id');
    }

    public function artisticStyle(): BelongsTo
    {
        return $this->belongsTo(ArtisticStyle::class);
    }

    public function providerLog(): HasOne
    {
        return $this->hasOne(ProviderLog::class);
    }
}
