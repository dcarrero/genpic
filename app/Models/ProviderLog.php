<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'generation_id', 'generation_session_id',
        'provider', 'model', 'status',
        'elapsed_ms', 'cost_usd', 'error_message',
        'request_summary', 'response_summary',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd'         => 'decimal:4',
            'request_summary'  => 'array',
            'response_summary' => 'array',
            'created_at'       => 'datetime',
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GenerationSession::class, 'generation_session_id');
    }

    public static function record(
        GenerationSession $session,
        string $provider,
        string $status,
        ?int $elapsedMs = null,
        ?float $costUsd = null,
        ?string $model = null,
        ?string $errorMessage = null,
        ?Generation $generation = null,
    ): self {
        return self::create([
            'generation_session_id' => $session->id,
            'generation_id'         => $generation?->id,
            'provider'              => $provider,
            'model'                 => $model,
            'status'                => $status,
            'elapsed_ms'            => $elapsedMs,
            'cost_usd'              => $costUsd,
            'error_message'         => $errorMessage,
        ]);
    }
}
