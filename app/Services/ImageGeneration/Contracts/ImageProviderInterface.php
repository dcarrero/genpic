<?php

namespace App\Services\ImageGeneration\Contracts;

interface ImageProviderInterface
{
    /**
     * Nombre del proveedor (para logs y config).
     */
    public function getName(): string;

    /**
     * Generar una imagen a partir de los parámetros dados.
     *
     * @param array $params [
     *   'image_url'      => string,  // URL firmada de la foto original
     *   'prompt'         => string,  // Prompt positivo completo
     *   'negative_prompt'=> string,  // Prompt negativo
     *   'style_strength' => float,   // 0.0-1.0
     *   'width'          => int,
     *   'height'         => int,
     *   'seed'           => int|null,
     * ]
     * @return array [
     *   'image'    => string, // Contenido binario de la imagen generada
     *   'provider' => string, // Nombre del proveedor
     *   'model'    => string, // Modelo usado
     *   'seed'     => int,    // Seed usado (para reproducibilidad)
     *   'elapsed_ms' => int,  // Tiempo de ejecución
     * ]
     */
    public function generate(array $params): array;

    /**
     * Coste estimado en USD por generación.
     */
    public function estimateCost(array $params): float;

    /**
     * Si el proveedor está configurado y habilitado.
     */
    public function isAvailable(): bool;
}
