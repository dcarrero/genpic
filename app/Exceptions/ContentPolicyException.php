<?php

namespace App\Exceptions;

/**
 * Lanzada cuando un proveedor IA rechaza contenido por politica de contenido.
 * Esta excepcion ROMPE la cadena de proveedores — no se intenta el siguiente.
 */
class ContentPolicyException extends \RuntimeException
{
    public function __construct(
        public readonly string $providerName,
        public readonly string $originalMessage,
    ) {
        parent::__construct("Content policy violation from {$providerName}: {$originalMessage}");
    }
}
