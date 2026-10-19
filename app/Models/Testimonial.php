<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'quote', 'author', 'tag', 'rating', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quote'  => 'array',
            'author' => 'array',
            'tag'    => 'array',
            'active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Obtener N testimonios aleatorios activos.
     */
    public static function random(int $count = 6): \Illuminate\Support\Collection
    {
        return static::active()->inRandomOrder()->take($count)->get();
    }

    public function t(string $field, string $locale = 'es'): string
    {
        $data = $this->{$field};
        if (!is_array($data)) return $data ?? '';
        return $data[$locale] ?? $data['en'] ?? $data['es'] ?? (reset($data) ?: '');
    }
}
