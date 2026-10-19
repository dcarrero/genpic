<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Obtener un setting por key, con cache de 1 hora.
     */
    /**
     * Mapa de claves SiteSetting → config() para fallback.
     * Necesario porque env() no funciona con config:cache.
     */
    private static array $configMap = [
        'google_analytics_id' => 'genpic.google_analytics_id',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            // Primero BD
            $dbValue = static::where('key', $key)->value('value');
            if ($dbValue !== null && $dbValue !== '') return $dbValue;

            // Fallback: config() (compatible con config:cache)
            if (isset(self::$configMap[$key])) {
                $configValue = config(self::$configMap[$key]);
                if ($configValue !== null && $configValue !== '') return $configValue;
            }

            return $default;
        });
    }

    /**
     * Indica de dónde viene el valor: 'db', 'config', o null si no existe.
     */
    public static function source(string $key): ?string
    {
        $dbValue = static::where('key', $key)->value('value');
        if ($dbValue !== null && $dbValue !== '') return 'db';

        if (isset(self::$configMap[$key])) {
            $configValue = config(self::$configMap[$key]);
            if ($configValue !== null && $configValue !== '') return 'env';
        }

        return null;
    }

    /**
     * Guardar un setting y limpiar cache.
     */
    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget("site_setting_{$key}");
    }
}
