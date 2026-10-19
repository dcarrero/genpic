<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtisticStyle extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\CachedQueries::flushStyles());
        static::deleted(fn () => \App\Services\CachedQueries::flushStyles());
    }

    protected $fillable = [
        'style_key', 'style_category', 'label', 'description',
        'label_es', 'label_en', 'description_es', 'description_en',
        'base_prompt', 'negative_prompt', 'style_strength', 'max_faces',
        'preview_image', 'phase', 'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return [
            'label'          => 'array',
            'description'    => 'array',
            'style_strength' => 'float',
            'max_faces'      => 'integer',
            'active'         => 'boolean',
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

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }
}
