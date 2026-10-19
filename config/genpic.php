<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idiomas soportados — añadir aquí para que aparezcan en admin
    |--------------------------------------------------------------------------
    */
    'locales' => array_filter(array_map('trim', explode(',', env('APP_LOCALES', 'es,en,fr,pt,it')))),

    // Mapa de locales: nombre nativo, og:locale, Stripe locale
    'locale_map' => [
        'es' => ['name' => 'Español',    'flag' => '🇪🇸', 'og' => 'es_ES', 'stripe' => 'es'],
        'en' => ['name' => 'English',    'flag' => '🇺🇸', 'og' => 'en_US', 'stripe' => 'en'],
        'fr' => ['name' => 'Français',   'flag' => '🇫🇷', 'og' => 'fr_FR', 'stripe' => 'fr'],
        'pt' => ['name' => 'Português',  'flag' => '🇧🇷', 'og' => 'pt_PT', 'stripe' => 'pt'],
        'it' => ['name' => 'Italiano',   'flag' => '🇮🇹', 'og' => 'it_IT', 'stripe' => 'it'],
    ],

    // Google Analytics — fallback para SiteSetting si no está en BD
    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),

    // Cache de páginas públicas (HTML completo). 0 = desactivado.
    'cache_public_pages_hours' => (int) env('CACHE_PUBLIC_PAGES_HOURS', 168), // 7 días

    // Path del panel admin. Cambiarlo es solo ocultación: la protección real
    // es ADMIN_ALLOWED_IPS + ADMIN_EMAILS.
    'admin_path' => env('ADMIN_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Titular del servicio — aparece en pie de página, emails y metadatos
    |--------------------------------------------------------------------------
    | Los textos legales (lang/{locale}/ui.php, claves legal_...) llevan datos de
    | ejemplo: hay que reescribirlos con los de quien despliegue.
    */
    'company' => [
        'name' => env('COMPANY_NAME', 'Example Company S.L.'),
    ],
    'contact_email' => env('CONTACT_EMAIL', 'hello@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Tiempos de retención
    |--------------------------------------------------------------------------
    */
    'session_ttl_days' => env('GENERATION_SESSION_TTL_DAYS', 14),
    'download_link_ttl_hours' => env('DOWNLOAD_LINK_TTL_HOURS', 336), // 14 días
    'file_purge_grace_days' => env('FILE_PURGE_GRACE_DAYS', 1),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'generations_per_ip' => env('RATE_LIMIT_GENERATIONS_PER_IP', 3),
        'period_hours' => env('RATE_LIMIT_PERIOD_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Moderation (deteccion NSFW / contenido prohibido)
    |--------------------------------------------------------------------------
    | Capa 1: analiza foto antes de enviar a proveedores IA
    | Si detecta contenido prohibido: ban IP, notifica admin, bloquea upload
    */
    'content_moderation' => [
        'enabled' => env('CONTENT_MODERATION_ENABLED', true),
        'provider' => env('CONTENT_MODERATION_PROVIDER', 'huggingface'),
        'ban_duration_hours' => env('CONTENT_MODERATION_BAN_HOURS', 72),
        'thresholds' => [
            'nudity' => (float) env('MODERATION_THRESHOLD_NUDITY', 0.60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Verificación PIN por email
    |--------------------------------------------------------------------------
    | false = acceso directo tras confirmar email (flujo rápido)
    | true = requiere PIN de 6 dígitos enviado al email (más seguro)
    */
    'require_pin' => env('REQUIRE_PIN_VERIFICATION', false),

    /*
    |--------------------------------------------------------------------------
    | Cierre del servicio
    |--------------------------------------------------------------------------
    | CLOSED_MODE=true cierra las altas y las compras: el funnel de creacion
    | (/create) y el de pago (/checkout) devuelven 410 con la pagina de cierre,
    | y se muestra un aviso permanente en toda la web.
    |
    | Lo que SIGUE funcionando: galeria, estilos, landings, legales,
    | /my-downloads, /portrait y las descargas firmadas — para que quien tenga
    | un retrato pendiente pueda recuperarlo.
    |
    | Tambien silencia los emails de marketing (abandono, upgrade, expiracion):
    | no tiene sentido invitar a comprar en un servicio cerrado.
    */
    'closing' => [
        'enabled' => (bool) env('CLOSED_MODE', false),
        // Fecha de cierre efectivo (YYYY-MM-DD). Solo para el texto publico.
        'date' => env('CLOSED_MODE_DATE', '2026-09-30'),
        // Repo publico del codigo liberado. Vacio = no se muestra el enlace.
        'repo_url' => env('OPENSOURCE_REPO_URL', ''),

        // Hasta cuando se pueden recuperar los retratos ya generados. NO es la
        // fecha de cierre: cerrar las altas no borra lo que ya existe. Vacia =
        // se calcula como fecha de cierre + session_ttl_days. Si la fecha ya
        // pasó, la pagina deja de dar plazo en vez de anunciar uno vencido.
        'downloads_until' => env('CLOSED_DOWNLOADS_UNTIL', ''),

        // Otros proyectos que se recomiendan desde la pagina de cierre.
        // La descripcion de cada uno vive en ui.closing_project_{key}.
        'projects' => [
            ['key' => 'mbox',        'domain' => 'mboxviewerpro.com'],
            ['key' => 'iskitch',     'domain' => 'iskitch.com'],
            ['key' => 'flarepurge',  'domain' => 'flarepurge.com'],
            ['key' => 'killexif',    'domain' => 'killexif.com'],
            ['key' => 'files',       'domain' => 'files.co'],
            ['key' => 'reader',      'domain' => 'reader.me'],
            ['key' => 'password',    'domain' => 'password.es'],
            ['key' => 'spyonweb',    'domain' => 'spyonweb.net'],
            ['key' => 'dnsgratis',   'domain' => 'dnsgratis.com'],
            ['key' => 'linuxgratis', 'domain' => 'linuxgratis.com'],
            ['key' => 'meteo',       'domain' => 'meteo.es'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo mantenimiento por .env
    |--------------------------------------------------------------------------
    */
    'maintenance_mode' => env('MAINTENANCE_MODE', false),
    'admin_emails' => array_filter(array_map(
        'trim',
        explode(',', env('ADMIN_EMAILS', ''))
    )),
    'admin_allowed_ips' => array_filter(array_map(
        'trim',
        explode(',', env('ADMIN_ALLOWED_IPS', ''))
    )),

    /*
    |--------------------------------------------------------------------------
    | Requerir email antes de generar
    |--------------------------------------------------------------------------
    | false = genera directo tras subir foto, sin pedir email (más conversión, más coste IA)
    | true  = muestra campo email antes de generar:
    |         - Si PIN=false: email OPCIONAL, genera directo, avisa por email al terminar
    |         - Si PIN=true:  email OBLIGATORIO + verificación PIN antes de generar
    |         En ambos casos, si no dio email en el formulario, se le ofrece durante el polling
    */
    'require_email_before_generation' => env('REQUIRE_EMAIL_BEFORE_GENERATION', false),

    /*
    |--------------------------------------------------------------------------
    | Face Detection (client-side con face-api.js)
    |--------------------------------------------------------------------------
    | false = permite generar sin detectar caras (útil para debug)
    | true = requiere al menos una cara detectada antes de generar
    */
    'face_detection_enabled' => env('FACE_DETECTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Web Basic gratis (promo temporal)
    |--------------------------------------------------------------------------
    | true = web_basic se puede descargar gratis con email (marca cortesía)
    | false = flujo normal de pago
    */
    'free_web_basic_enabled' => env('FREE_WEB_BASIC_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Watermark
    |--------------------------------------------------------------------------
    */
    'watermark' => [
        'text' => env('WATERMARK_TEXT', 'GenPic preview'),
        'font_size' => 8,
        'opacity' => 0.45,
        'rotation' => -35,
        'spacing_px' => 38,
        'color' => '#FFFFFF',
        'shadow' => '#000000',
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatos de exportación
    |--------------------------------------------------------------------------
    */
    'formats' => [
        'web_basic' => [
            'max_dimension' => 1024,
            'type' => 'jpg',
            'quality' => 85,
        ],
        'web_hd' => [
            'max_dimension' => 2048,
            'type' => 'jpg',
            'quality' => 90,
        ],
        'mobile_wallpaper' => [
            'width' => 1080,
            'height' => 2340,
            'type' => 'jpg',
            'quality' => 90,
            'regenerate' => true,  // Regenerar si orientación no coincide
        ],
        'desktop_wallpaper' => [
            'width' => 2560,
            'height' => 1440,
            'type' => 'jpg',
            'quality' => 90,
            'regenerate' => true,  // Regenerar si orientación no coincide
        ],
        'print_premium' => [
            'max_dimension' => 4096,
            'type' => 'png',
            'quality' => 100,
            'upscale' => true,  // Upscale 2x solo si raw < 4096px
        ],
        'original_png' => [
            'type' => 'png',
            'quality' => 100,
            'raw' => true,  // Usar PNG original de la IA sin redimensionar
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wasabi S3
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => 'wasabi',
        'paths' => [
            'originals' => 'originals',
            'generations' => 'generations',
            'watermarked' => 'watermarked',
            'exports' => 'exports',
        ],
    ],

];
