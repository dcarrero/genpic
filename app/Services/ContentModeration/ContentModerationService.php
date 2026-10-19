<?php

namespace App\Services\ContentModeration;

use App\Services\ContentModeration\Contracts\ModerationProviderInterface;
use App\Services\ContentModeration\Providers\HuggingFaceNsfwProvider;
use Illuminate\Support\Facades\Log;

class ContentModerationService
{
    private ?ModerationProviderInterface $provider = null;

    private static array $providerMap = [
        'huggingface' => HuggingFaceNsfwProvider::class,
    ];

    public function __construct()
    {
        if (!config('genpic.content_moderation.enabled')) {
            return;
        }

        $name = config('genpic.content_moderation.provider', 'sightengine');

        if (isset(self::$providerMap[$name])) {
            $provider = app(self::$providerMap[$name]);
            if ($provider->isAvailable()) {
                $this->provider = $provider;
            } else {
                Log::warning("Content moderation provider '{$name}' no disponible (credenciales faltantes)");
            }
        } else {
            Log::warning("Content moderation provider '{$name}' no reconocido");
        }
    }

    /**
     * Analizar imagen para contenido prohibido.
     * Si el servicio esta deshabilitado o no hay provider, retorna "pass".
     */
    public function analyze(string $imageContents): ModerationResult
    {
        if (!$this->provider) {
            return ModerationResult::pass('disabled');
        }

        return $this->provider->analyze($imageContents);
    }

    public function isEnabled(): bool
    {
        return $this->provider !== null;
    }
}
