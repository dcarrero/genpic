<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'stripe_coupon_id', 'stripe_promotion_code_id',
        'type', 'value', 'product_keys',
        'max_uses', 'times_used', 'once_per_email',
        'first_time_only', 'minimum_amount_cents',
        'valid_from', 'valid_until',
        'auto_generated', 'active',
    ];

    protected function casts(): array
    {
        return [
            'product_keys'   => 'array',
            'once_per_email' => 'boolean',
            'first_time_only' => 'boolean',
            'auto_generated' => 'boolean',
            'active'         => 'boolean',
            'valid_from'     => 'datetime',
            'valid_until'    => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()));
    }

    public function scopeManual($query)
    {
        return $query->where('auto_generated', false);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses && $this->times_used >= $this->max_uses;
    }

    public function appliesToProduct(string $productKey): bool
    {
        if (!$this->product_keys) return true;
        return in_array($productKey, $this->product_keys);
    }

    public function discountCents(int $subtotalCents): int
    {
        if ($this->type === 'percent') {
            return (int) round($subtotalCents * $this->value / 100);
        }
        return min($this->value, $subtotalCents);
    }

    public function formattedValue(): string
    {
        if ($this->type === 'percent') {
            return $this->value . '%';
        }
        return number_format($this->value / 100, 2, ',', '.') . '€';
    }
}
