<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_key', 'sequence_type', 'name', 'description', 'active',
        'delay_hours', 'email_number',
        'discount_percent', 'discount_product_key',
        'subject', 'heading', 'body', 'cta_text', 'coupon_text',
    ];

    protected function casts(): array
    {
        return [
            'active'      => 'boolean',
            'subject'     => 'array',
            'heading'     => 'array',
            'body'        => 'array',
            'cta_text'    => 'array',
            'coupon_text' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeAbandonment($query)
    {
        return $query->where('sequence_type', 'abandonment');
    }

    public function scopeUpgrade($query)
    {
        return $query->where('sequence_type', 'upgrade');
    }

    public function hasDiscount(): bool
    {
        return $this->discount_percent > 0;
    }

    /**
     * Obtener texto traducido de un campo JSON.
     * Fallback: locale → 'en' → 'es' → primer valor disponible.
     */
    public function t(string $field, string $locale = 'es'): string
    {
        $data = $this->{$field};
        if (!is_array($data)) return $data ?? '';

        return $data[$locale]
            ?? $data['en']
            ?? $data['es']
            ?? (reset($data) ?: '');
    }

    public function subject(string $locale = 'es'): string
    {
        return $this->t('subject', $locale);
    }
}
