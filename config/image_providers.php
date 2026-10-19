<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cadena de proveedores IA — orden de prioridad
    |--------------------------------------------------------------------------
    | El ImageGenerationService prueba cada proveedor en este orden.
    | Si uno falla o supera el timeout, pasa al siguiente.
    | fal.ai primero (respeta aspect ratio nativo), OpenRouter segundo, resto como respaldo.
    */
    'chain' => array_filter(array_map('trim', explode(',', env('AI_PROVIDER_CHAIN', 'fal_ai,openrouter,novita,evolink')))),

    /*
    |--------------------------------------------------------------------------
    | Presupuesto diario de coste IA (USD)
    |--------------------------------------------------------------------------
    */
    'daily_budget_usd' => env('DAILY_COST_BUDGET_USD', 50),

    /*
    |--------------------------------------------------------------------------
    | Cooldown tras fallo (segundos)
    |--------------------------------------------------------------------------
    */
    'cooldown_timeout' => 300,  // 5 min tras timeout
    'cooldown_error'   => 120,  // 2 min tras error genérico

    /*
    |--------------------------------------------------------------------------
    | Proveedores de generación
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'fal_ai' => [
            'enabled'         => env('FAL_AI_ENABLED', true),
            'key'             => env('FAL_AI_KEY'),
            'base_url'        => 'https://fal.run',
            'model'           => 'fal-ai/bytedance/seedream/v5/lite/edit',
            'timeout_seconds' => 120,
            'cost_per_image'  => 0.035,
        ],

        'openrouter' => [
            'enabled'         => env('OPENROUTER_ENABLED', true),
            'key'             => env('OPENROUTER_API_KEY'),
            'base_url'        => 'https://openrouter.ai/api/v1',
            'model'           => env('OPENROUTER_DEFAULT_MODEL', 'bytedance-seed/seedream-4.5'),
            'image_size'      => env('OPENROUTER_IMAGE_SIZE', '4K'),
            'timeout_seconds' => 120,
            'cost_per_image'  => 0.040,
        ],

        'evolink' => [
            'enabled'         => env('EVOLINK_ENABLED', false),
            'key'             => env('EVOLINK_API_KEY'),
            'base_url'        => 'https://api.evolink.ai',
            'model'           => 'doubao-seedream-4.5',
            'timeout_seconds' => 120,
            'cost_per_image'  => 0.031,
        ],

        'novita' => [
            'enabled'         => env('NOVITA_ENABLED', false),
            'key'             => env('NOVITA_API_KEY'),
            'base_url'        => 'https://api.novita.ai',
            'model'           => 'seedream-4.5',
            'timeout_seconds' => 120,
            'cost_per_image'  => 0.030,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Upscale (Real-ESRGAN)
    |--------------------------------------------------------------------------
    | Proveedor para upscale 2x en Print Premium. Por ahora solo fal.ai.
    | Coste registrado aparte para estadísticas.
    */
    'upscale' => [
        'provider'       => env('UPSCALE_PROVIDER', 'fal_ai'),
        'cost_per_image' => 0.010, // USD estimado por upscale
    ],

];
