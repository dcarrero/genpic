<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'generation_id',
        'quantity', 'unit_price_cents', 'total_cents',
        'export_paths', 'exports_ready', 'exports_size_bytes',
        'is_upsell', 'artistic_style_id', 'generation_status',
    ];

    protected function casts(): array
    {
        return [
            'export_paths'  => 'array',
            'exports_ready' => 'boolean',
            'is_upsell'     => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function artisticStyle(): BelongsTo
    {
        return $this->belongsTo(ArtisticStyle::class);
    }

    public function needsGeneration(): bool
    {
        return $this->is_upsell && !$this->generation_id;
    }
}
