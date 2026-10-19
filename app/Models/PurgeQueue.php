<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurgeQueue extends Model
{
    use HasFactory;

    protected $table = 'purge_queue';

    protected $fillable = [
        'generation_session_id', 'paths_to_purge', 'estimated_bytes',
        'status', 'scheduled_at', 'processed_at', 'bytes_freed', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'paths_to_purge' => 'array',
            'scheduled_at'   => 'datetime',
            'processed_at'   => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GenerationSession::class, 'generation_session_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_at', '<=', now());
    }
}
