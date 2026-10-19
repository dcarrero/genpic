<?php

namespace App\Services\ContentModeration;

class ModerationResult
{
    public function __construct(
        public readonly bool $isProhibited,
        public readonly ?string $violationType,
        public readonly ?float $score,
        public readonly array $rawResponse,
        public readonly string $provider,
    ) {}

    /**
     * Resultado "pasa" — sin violacion detectada.
     */
    public static function pass(string $provider = 'none'): static
    {
        return new static(
            isProhibited: false,
            violationType: null,
            score: null,
            rawResponse: [],
            provider: $provider,
        );
    }
}
