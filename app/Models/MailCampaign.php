<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailCampaign extends Model
{
    protected $fillable = [
        'name', 'subject', 'body', 'coupon_code', 'target_source',
        'status', 'batch_size', 'total_recipients', 'sent_count', 'failed_count',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subject'    => 'array',
            'body'       => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function t(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $data = $this->{$field};
        if (!is_array($data)) return $data ?? '';
        return $data[$locale] ?? $data['en'] ?? $data['es'] ?? (reset($data) ?: '');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(MailSend::class, 'campaign_id');
    }

    public function pendingSends(): HasMany
    {
        return $this->sends()->where('status', 'pending');
    }

    public function scopeDraft($query) { return $query->where('status', 'draft'); }
    public function scopeSending($query) { return $query->where('status', 'sending'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }

    public function progressPercent(): int
    {
        if ($this->total_recipients === 0) return 0;
        return (int) round(($this->sent_count + $this->failed_count) / $this->total_recipients * 100);
    }

    /**
     * Mapa de country codes a idioma para detección automática.
     */
    public static function languageMap(): array
    {
        return [
            'es' => ['ES', 'MX', 'AR', 'CO', 'CL', 'PE', 'VE', 'EC', 'GT', 'CU', 'BO', 'DO', 'HN', 'PY', 'SV', 'NI', 'CR', 'PA', 'UY'],
            'fr' => ['FR', 'BE', 'CH', 'CA', 'LU', 'MC', 'SN', 'CI', 'ML', 'BF', 'NE', 'TG', 'BJ', 'GA', 'CG', 'CD', 'MG', 'CM'],
            'it' => ['IT', 'SM', 'VA'],
            'pt' => ['PT', 'BR', 'AO', 'MZ', 'CV'],
        ];
    }

    public static function detectLanguage(?string $countryCode): string
    {
        if (!$countryCode) return 'en';
        $cc = strtoupper(trim($countryCode));
        foreach (self::languageMap() as $lang => $countries) {
            if (in_array($cc, $countries, true)) return $lang;
        }
        return 'en';
    }
}
