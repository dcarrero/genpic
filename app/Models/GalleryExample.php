<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryExample extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn ($m) => \App\Services\CachedQueries::flushGallery($m->id));
        static::deleted(fn ($m) => \App\Services\CachedQueries::flushGallery($m->id));
    }

    protected $fillable = [
        'title', 'title_es', 'title_en', 'photo_type',
        'artistic_style_id', 'style_key',
        'original_path', 'result_path', 'thumbnail_path',
        'featured', 'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return [
            'title'    => 'array',
            'featured' => 'boolean',
            'active'   => 'boolean',
        ];
    }

    public function t(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $data = $this->{$field};
        if (!is_array($data)) return $data ?? '';
        return $data[$locale] ?? $data['en'] ?? $data['es'] ?? (reset($data) ?: '');
    }

    public function artisticStyle(): BelongsTo
    {
        return $this->belongsTo(ArtisticStyle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)->where('active', true)->orderBy('sort_order');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('photo_type', $type);
    }

    public function title(string $locale = 'es'): string
    {
        return $locale === 'en'
            ? ($this->title_en ?? $this->title_es ?? '')
            : ($this->title_es ?? '');
    }

    public static function photoTypeLabels(): array
    {
        return [
            'family'     => ['es' => 'Familias', 'en' => 'Families'],
            'couple'     => ['es' => 'Parejas', 'en' => 'Couples'],
            'individual' => ['es' => 'Retratos', 'en' => 'Portraits'],
            'group'      => ['es' => 'Grupos', 'en' => 'Groups'],
        ];
    }
}
