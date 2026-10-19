<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\CachedQueries::flushProducts());
        static::deleted(fn () => \App\Services\CachedQueries::flushProducts());
    }

    protected $fillable = [
        'product_key', 'name', 'description',
        'name_es', 'name_en', 'description_es', 'description_en',
        'price_cents', 'currency',
        // Futuro Fase B: precios por región
        'price_es_cents', 'price_en_cents', 'currency_es', 'currency_en',
        'stripe_price_id_es', 'stripe_price_id_en',
        'type', 'sort_order', 'highlight', 'active',
        'is_upsell_addon', 'upsell_for_product_id', 'max_quantity',
    ];

    protected function casts(): array
    {
        return [
            'name'            => 'array',
            'description'     => 'array',
            'highlight'       => 'boolean',
            'active'          => 'boolean',
            'is_upsell_addon' => 'boolean',
        ];
    }

    public function t(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $data = $this->{$field};
        if (!is_array($data)) return $data ?? '';
        return $data[$locale] ?? $data['en'] ?? $data['es'] ?? (reset($data) ?: '');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }

    public function formats(): BelongsToMany
    {
        return $this->belongsToMany(Format::class, 'product_formats');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function upsellParent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'upsell_for_product_id');
    }

    public function upsellAddons(): HasMany
    {
        return $this->hasMany(Product::class, 'upsell_for_product_id');
    }

    public function scopeMainProducts($query)
    {
        return $query->where('is_upsell_addon', false)->where('active', true)->orderBy('sort_order');
    }

    /**
     * Precio en céntimos de EUR. Único precio, Stripe convierte moneda al pagar.
     */
    public function priceCents(): int
    {
        // Usa price_cents si existe, fallback a price_es_cents para compatibilidad
        return $this->price_cents ?? $this->price_es_cents ?? 0;
    }

    /**
     * Precio formateado en EUR.
     */
    public function formattedPrice(): string
    {
        return number_format($this->priceCents() / 100, 2, ',', '.') . '€';
    }
}
