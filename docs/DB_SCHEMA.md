# Esquema de base de datos

> Documento de diseño previo a la implementación (el proyecto se llamó RetratosIA
> al principio). La fuente de verdad son `database/migrations/` y
> `database/seeders/`; donde no coincidan, manda el código.

**Motor:** MariaDB 10.11  
**Charset:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Convención:** snake_case, IDs bigint unsigned auto-increment, timestamps en todas las tablas.

---

## Diagrama de Relaciones

```
users
  └─── generation_sessions (1:N)
            └─── generations (1:N)
            └─── orders (1:N)
                      └─── order_items (1:N)
                               └─── products (N:1)
                                        └─── formats (N:M via product_formats)

artistic_styles ←── generations (N:1)
artistic_styles ←── generation_sessions (N:1)
provider_logs   ←── generations (1:1)
purge_queue     ←── generation_sessions (1:1)
```

---

## Tablas

---

### `users`

```sql
CREATE TABLE users (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email                   VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at       TIMESTAMP NULL,
    locale                  VARCHAR(10) NOT NULL DEFAULT 'es',  -- 'es' | 'en'
    interested_in_print     TINYINT(1) NOT NULL DEFAULT 0,      -- Fase 4: lista espera
    stripe_customer_id      VARCHAR(100) NULL,
    marketing_opt_in        TINYINT(1) NOT NULL DEFAULT 0,
    ip_address              VARCHAR(45) NULL,                    -- IPv4/IPv6
    user_agent              TEXT NULL,
    last_seen_at            TIMESTAMP NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_stripe_customer (stripe_customer_id)
) ENGINE=InnoDB;

-- Nota: NO hay password. Acceso solo via signed URLs en email.
-- En Fase 3+ si se añade suscripción se plantea auth con magic link o social.
```

---

### `artistic_styles`

```sql
CREATE TABLE artistic_styles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    style_key       VARCHAR(50) NOT NULL UNIQUE,    -- 'renacimiento', 'velazquez', etc.
    label_es        VARCHAR(100) NOT NULL,
    label_en        VARCHAR(100) NOT NULL,
    description_es  TEXT NULL,
    description_en  TEXT NULL,
    base_prompt     TEXT NOT NULL,                  -- Prompt base para el modelo
    negative_prompt TEXT NULL,
    style_strength  DECIMAL(3,2) NOT NULL DEFAULT 0.75,  -- 0.0-1.0
    preview_image   VARCHAR(500) NULL,              -- Wasabi path de imagen de ejemplo
    phase           TINYINT UNSIGNED NOT NULL DEFAULT 1,  -- En qué fase se activa
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    active          TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active_sort (active, sort_order),
    INDEX idx_style_key (style_key)
) ENGINE=InnoDB;
```

---

### `formats`

```sql
CREATE TABLE formats (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    format_key      VARCHAR(50) NOT NULL UNIQUE,   -- 'web', 'mobile_wallpaper', etc.
    label_es        VARCHAR(100) NOT NULL,
    label_en        VARCHAR(100) NOT NULL,
    width_px        SMALLINT UNSIGNED NULL,         -- NULL = flexible (lado mayor)
    height_px       SMALLINT UNSIGNED NULL,
    max_dimension_px INT UNSIGNED NULL,             -- Para formatos proporcionales
    file_type       ENUM('jpg','png','tiff') NOT NULL DEFAULT 'jpg',
    quality         TINYINT UNSIGNED NOT NULL DEFAULT 85,  -- JPEG quality 0-100
    description_es  VARCHAR(255) NULL,
    description_en  VARCHAR(255) NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Registros iniciales:
-- 'web'              → max_dimension 2048, JPG Q85
-- 'mobile_wallpaper' → 1080×2340, JPG Q90
-- 'desktop_wallpaper'→ 2560×1440, JPG Q90
-- 'print_premium'    → max_dimension 4000, PNG Q100
```

---

### `products`

```sql
CREATE TABLE products (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_key         VARCHAR(50) NOT NULL UNIQUE,
    name_es             VARCHAR(150) NOT NULL,
    name_en             VARCHAR(150) NOT NULL,
    description_es      TEXT NULL,
    description_en      TEXT NULL,
    price_es_cents      INT UNSIGNED NOT NULL,      -- Precio en céntimos para ES/LATAM
    price_en_cents      INT UNSIGNED NOT NULL,      -- Precio en cents para EN markets
    currency_es         VARCHAR(3) NOT NULL DEFAULT 'EUR',
    currency_en         VARCHAR(3) NOT NULL DEFAULT 'USD',
    stripe_price_id_es  VARCHAR(100) NULL,          -- ID de precio en Stripe (EUR)
    stripe_price_id_en  VARCHAR(100) NULL,          -- ID de precio en Stripe (USD)
    type                ENUM('digital','physical') NOT NULL DEFAULT 'digital',
    sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    highlight           TINYINT(1) NOT NULL DEFAULT 0,  -- Mostrar como "Más popular"
    active              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active_sort (active, sort_order)
) ENGINE=InnoDB;
```

---

### `product_formats`

```sql
-- Tabla pivote: qué formatos incluye cada producto
CREATE TABLE product_formats (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  BIGINT UNSIGNED NOT NULL,
    format_id   BIGINT UNSIGNED NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_product_format (product_id, format_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (format_id)  REFERENCES formats(id)  ON DELETE CASCADE
) ENGINE=InnoDB;
```

---

### `generation_sessions`

```sql
-- Una sesión por upload. Vincula al usuario con sus generaciones y pedidos.
CREATE TABLE generation_sessions (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                    CHAR(36) NOT NULL UNIQUE,       -- Para URLs
    user_id                 BIGINT UNSIGNED NULL,           -- NULL hasta captura email
    style_key               VARCHAR(50) NOT NULL,
    artistic_style_id       BIGINT UNSIGNED NULL,

    -- Foto original
    original_photo_path     VARCHAR(500) NULL,              -- Wasabi key
    original_filename       VARCHAR(255) NULL,
    original_size_bytes     INT UNSIGNED NULL,
    face_detected           TINYINT(1) NULL,                -- NULL = no procesado

    -- Estado global de la sesión
    status                  ENUM(
                                'pending',      -- recién creada, esperando job
                                'processing',   -- job en ejecución
                                'watermarked',  -- preview listo
                                'paid',         -- al menos un order pagado
                                'failed',       -- todos los intentos fallaron
                                'expired'       -- TTL expirado y archivos purgados
                            ) NOT NULL DEFAULT 'pending',

    -- Email capture tracking
    email_captured_at       TIMESTAMP NULL,
    session_email_sent_at   TIMESTAMP NULL,

    -- Recuperación de abandono (Fase 2)
    email_recovery_sent_at  TIMESTAMP NULL,
    expiry_warning_sent_at  TIMESTAMP NULL,

    -- TTL y purga
    expires_at              TIMESTAMP NOT NULL,             -- created_at + 14 días
    purge_scheduled_at      TIMESTAMP NULL,
    purged_at               TIMESTAMP NULL,

    -- Analytics (Fase 2)
    ab_variant              VARCHAR(20) NULL,
    utm_source              VARCHAR(100) NULL,
    utm_medium              VARCHAR(100) NULL,
    utm_campaign            VARCHAR(100) NULL,
    referrer                VARCHAR(500) NULL,

    -- Meta
    ip_address              VARCHAR(45) NULL,
    locale                  VARCHAR(10) NOT NULL DEFAULT 'es',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_uuid (uuid),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id)           REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (artistic_style_id) REFERENCES artistic_styles(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

### `generations`

```sql
-- Una generation por intento de IA. Una sesión puede tener N generaciones
-- (reintento por fallo, o upsell de segundo estilo en Fase 2)
CREATE TABLE generations (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                    CHAR(36) NOT NULL UNIQUE,
    generation_session_id   BIGINT UNSIGNED NOT NULL,
    artistic_style_id       BIGINT UNSIGNED NULL,

    -- Estado
    status                  ENUM(
                                'queued',
                                'processing',
                                'completed',
                                'watermarked',
                                'failed'
                            ) NOT NULL DEFAULT 'queued',

    -- Proveedor
    provider                VARCHAR(50) NULL,               -- 'fal_ai', 'replicate', etc.
    provider_job_id         VARCHAR(255) NULL,              -- ID del job en el proveedor
    provider_model          VARCHAR(100) NULL,
    cost_usd                DECIMAL(8,4) NULL,              -- Coste real o estimado

    -- Archivos en Wasabi
    raw_image_path          VARCHAR(500) NULL,              -- generations/{session}/{uuid}.png
    watermarked_image_path  VARCHAR(500) NULL,              -- watermarked/{session}/{uuid}.jpg
    raw_image_size_bytes    INT UNSIGNED NULL,

    -- Prompt utilizado (para auditoría y mejora)
    prompt_used             TEXT NULL,
    negative_prompt_used    TEXT NULL,
    style_strength_used     DECIMAL(3,2) NULL,
    seed                    BIGINT NULL,

    -- Tiempos
    queued_at               TIMESTAMP NULL,
    processing_started_at   TIMESTAMP NULL,
    completed_at            TIMESTAMP NULL,
    elapsed_ms              INT UNSIGNED NULL,

    -- Error
    error_message           TEXT NULL,
    attempt_number          TINYINT UNSIGNED NOT NULL DEFAULT 1,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_session (generation_session_id),
    INDEX idx_status (status),
    INDEX idx_provider (provider),
    FOREIGN KEY (generation_session_id) REFERENCES generation_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (artistic_style_id)     REFERENCES artistic_styles(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

### `orders`

```sql
CREATE TABLE orders (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                    CHAR(36) NOT NULL UNIQUE,
    user_id                 BIGINT UNSIGNED NOT NULL,
    generation_session_id   BIGINT UNSIGNED NOT NULL,

    -- Stripe
    stripe_checkout_id      VARCHAR(255) NULL,
    stripe_payment_intent_id VARCHAR(255) NULL UNIQUE,
    stripe_customer_id      VARCHAR(100) NULL,

    -- Importes (siempre en céntimos / cents de la moneda)
    subtotal_cents          INT UNSIGNED NOT NULL DEFAULT 0,
    discount_cents          INT UNSIGNED NOT NULL DEFAULT 0,
    total_cents             INT UNSIGNED NOT NULL DEFAULT 0,
    currency                VARCHAR(3) NOT NULL DEFAULT 'EUR',
    coupon_code             VARCHAR(50) NULL,

    -- Estado
    status                  ENUM(
                                'pending',      -- Checkout creado, no pagado
                                'paid',         -- Webhook recibido, pago confirmado
                                'processing',   -- Generando formatos
                                'ready',        -- Formatos listos para descargar
                                'refunded',     -- Devuelto via Stripe
                                'failed'        -- Pago fallido
                            ) NOT NULL DEFAULT 'pending',

    -- Descarga
    download_ready          TINYINT(1) NOT NULL DEFAULT 0,
    download_email_sent_at  TIMESTAMP NULL,

    -- Físico (Fase 4)
    gelato_order_id         VARCHAR(100) NULL,
    shipping_address        JSON NULL,
    tracking_number         VARCHAR(100) NULL,
    shipped_at              TIMESTAMP NULL,

    -- Analytics
    ab_variant              VARCHAR(20) NULL,
    utm_source              VARCHAR(100) NULL,
    utm_medium              VARCHAR(100) NULL,
    utm_campaign            VARCHAR(100) NULL,

    paid_at                 TIMESTAMP NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_uuid (uuid),
    INDEX idx_user (user_id),
    INDEX idx_session (generation_session_id),
    INDEX idx_stripe_pi (stripe_payment_intent_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id)                 REFERENCES users(id),
    FOREIGN KEY (generation_session_id)   REFERENCES generation_sessions(id)
) ENGINE=InnoDB;
```

---

### `order_items`

```sql
CREATE TABLE order_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    generation_id   BIGINT UNSIGNED NULL,       -- Qué generación se compra

    quantity        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price_cents INT UNSIGNED NOT NULL,
    total_cents     INT UNSIGNED NOT NULL,

    -- Archivos generados para esta línea
    export_paths    JSON NULL,                  -- {'web': 'exports/...jpg', 'mobile': '...'}
    exports_ready   TINYINT(1) NOT NULL DEFAULT 0,
    exports_size_bytes INT UNSIGNED NULL,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id)    REFERENCES orders(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id)  REFERENCES products(id),
    FOREIGN KEY (generation_id) REFERENCES generations(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

### `provider_logs`

```sql
-- Registro detallado de cada llamada a proveedor IA. Para análisis de costes.
CREATE TABLE provider_logs (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    generation_id           BIGINT UNSIGNED NULL,
    generation_session_id   BIGINT UNSIGNED NULL,

    provider                VARCHAR(50) NOT NULL,
    model                   VARCHAR(100) NULL,
    status                  ENUM('success','timeout','error','skipped') NOT NULL,

    elapsed_ms              INT UNSIGNED NULL,
    cost_usd                DECIMAL(8,4) NULL,
    error_message           TEXT NULL,

    -- Request/response (útil para debugging — puede crecer mucho)
    request_summary         JSON NULL,          -- Solo params relevantes, no la imagen
    response_summary        JSON NULL,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_provider_date (provider, created_at),
    INDEX idx_status (status),
    INDEX idx_generation (generation_id),
    FOREIGN KEY (generation_id)         REFERENCES generations(id) ON DELETE SET NULL,
    FOREIGN KEY (generation_session_id) REFERENCES generation_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

### `purge_queue`

```sql
-- Cola de archivos a borrar. El job nocturno procesa esta tabla.
CREATE TABLE purge_queue (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    generation_session_id   BIGINT UNSIGNED NOT NULL UNIQUE,

    -- Paths a borrar (array de Wasabi keys)
    paths_to_purge          JSON NOT NULL,      -- ['originals/...', 'generations/...']
    estimated_bytes         INT UNSIGNED NULL,

    status                  ENUM('pending','processing','completed','error') NOT NULL DEFAULT 'pending',
    scheduled_at            TIMESTAMP NOT NULL, -- expires_at de la sesión
    processed_at            TIMESTAMP NULL,
    bytes_freed             INT UNSIGNED NULL,
    error_message           TEXT NULL,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status_scheduled (status, scheduled_at),
    FOREIGN KEY (generation_session_id) REFERENCES generation_sessions(id)
) ENGINE=InnoDB;
```

---

### `gallery_examples` (Fase 2)

```sql
CREATE TABLE gallery_examples (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artistic_style_id BIGINT UNSIGNED NOT NULL,
    image_path      VARCHAR(500) NOT NULL,       -- Wasabi key, servida via CDN
    label_es        VARCHAR(150) NULL,
    label_en        VARCHAR(150) NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_style_active (artistic_style_id, active, sort_order),
    FOREIGN KEY (artistic_style_id) REFERENCES artistic_styles(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

---

## Seeders

### `ArtisticStylesSeeder` — datos iniciales Fase 1

```php
ArtisticStyle::create([
    'style_key'    => 'renacimiento',
    'label_es'     => 'Renacimiento Clásico',
    'label_en'     => 'Classical Renaissance',
    'base_prompt'  => 'Renaissance oil painting portrait, 16th century Italian master style,
                       dramatic chiaroscuro lighting, rich jewel-tone colors, detailed brushwork,
                       noble attire with ornate details, museum quality artwork, Uffizi Gallery style',
    'negative_prompt' => 'modern clothing, photograph, digital art, cartoon, anime, 3d render,
                          blurry, low quality, text, watermark',
    'style_strength'  => 0.75,
    'phase'           => 1,
    'sort_order'      => 1,
    'active'          => true,
]);
// ... repetir para los otros 3 estilos de Fase 1
```

### `FormatsSeeder`

```php
Format::insert([
    ['format_key' => 'web',               'label_es' => 'Calidad Web',       'label_en' => 'Web Quality',
     'max_dimension_px' => 2048, 'file_type' => 'jpg', 'quality' => 85],
    ['format_key' => 'mobile_wallpaper',  'label_es' => 'Fondo de Móvil',    'label_en' => 'Mobile Wallpaper',
     'width_px' => 1080, 'height_px' => 2340, 'file_type' => 'jpg', 'quality' => 90],
    ['format_key' => 'desktop_wallpaper', 'label_es' => 'Fondo de Escritorio','label_en' => 'Desktop Wallpaper',
     'width_px' => 2560, 'height_px' => 1440, 'file_type' => 'jpg', 'quality' => 90],
    ['format_key' => 'print_premium',     'label_es' => 'Premium para Imprimir','label_en' => 'Print Premium',
     'max_dimension_px' => 4000, 'file_type' => 'png', 'quality' => 100],
]);
```

---

## Consultas Útiles de Mantenimiento

```sql
-- Sesiones expiradas con archivos pendientes de purgar
SELECT gs.uuid, gs.expires_at, gs.status, pq.status as purge_status
FROM generation_sessions gs
LEFT JOIN purge_queue pq ON pq.generation_session_id = gs.id
WHERE gs.expires_at < NOW()
  AND gs.status != 'paid'
  AND (pq.status IS NULL OR pq.status = 'pending')
ORDER BY gs.expires_at;

-- Coste por proveedor este mes
SELECT provider,
       COUNT(*) as total_calls,
       SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successes,
       ROUND(AVG(elapsed_ms)) as avg_ms,
       ROUND(SUM(cost_usd), 2) as total_cost_usd
FROM provider_logs
WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
GROUP BY provider
ORDER BY total_cost_usd DESC;

-- Conversión upload → pago por estilo
SELECT a.label_es,
       COUNT(DISTINCT gs.id) as total_sessions,
       COUNT(DISTINCT o.id) as total_orders,
       ROUND(COUNT(DISTINCT o.id) / COUNT(DISTINCT gs.id) * 100, 1) as conversion_pct
FROM generation_sessions gs
JOIN artistic_styles a ON a.style_key = gs.style_key
LEFT JOIN orders o ON o.generation_session_id = gs.id AND o.status = 'paid'
GROUP BY a.id
ORDER BY conversion_pct DESC;

-- Revenue últimos 30 días
SELECT DATE(paid_at) as dia,
       COUNT(*) as pedidos,
       ROUND(SUM(total_cents) / 100, 2) as revenue_eur
FROM orders
WHERE status = 'paid'
  AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(paid_at)
ORDER BY dia;
```
