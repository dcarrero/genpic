<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'generation_session_id',
        'stripe_checkout_id', 'stripe_payment_intent_id', 'stripe_customer_id',
        'stripe_amount_total', 'stripe_amount_discount', 'stripe_amount_tax',
        'stripe_currency', 'stripe_promotion_code',
        'subtotal_cents', 'discount_cents', 'total_cents', 'currency', 'coupon_code',
        'status', 'download_ready', 'download_email_sent_at',
        'gelato_order_id', 'shipping_address', 'tracking_number', 'shipped_at',
        'ab_variant', 'utm_source', 'utm_medium', 'utm_campaign',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'download_ready'         => 'boolean',
            'download_email_sent_at' => 'datetime',
            'shipping_address'       => 'array',
            'shipped_at'             => 'datetime',
            'paid_at'                => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            $order->uuid ??= Str::uuid()->toString();
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(GenerationSession::class, 'generation_session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'ready']);
    }

    /**
     * Scope: pedidos pagados reales (excluye regalos/0€) — para estadísticas.
     */
    public function scopePaidReal($query)
    {
        return $query->whereIn('status', ['paid', 'ready'])->where('total_cents', '>', 0);
    }

    public function baseItem()
    {
        return $this->items()->where('is_upsell', false)->first();
    }

    public function upsellItems()
    {
        return $this->items()->where('is_upsell', true);
    }

    public function hasUpsellItems(): bool
    {
        return $this->items()->where('is_upsell', true)->exists();
    }

    public function allItemsReady(): bool
    {
        return $this->items()->where('exports_ready', false)->doesntExist();
    }
}
