<?php

namespace App\Services\ContentModeration\Contracts;

use App\Services\ContentModeration\ModerationResult;

interface ModerationProviderInterface
{
    public function getName(): string;

    /**
     * Analizar contenido de imagen para detectar material prohibido.
     */
    public function analyze(string $imageContents): ModerationResult;

    public function isAvailable(): bool;
}
