<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Format extends Model
{
    use HasFactory;

    protected $fillable = [
        'format_key', 'label', 'description',
        'label_es', 'label_en', 'width_px', 'height_px',
        'max_dimension_px', 'file_type', 'quality', 'description_es',
        'description_en', 'active',
    ];

    protected function casts(): array
    {
        return [
            'label'       => 'array',
            'description' => 'array',
            'active'      => 'boolean',
        ];
    }

    public function t(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $data = $this->{$field};
        if (!is_array($data)) return $data ?? '';
        return $data[$locale] ?? $data['en'] ?? $data['es'] ?? (reset($data) ?: '');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_formats');
    }
}
