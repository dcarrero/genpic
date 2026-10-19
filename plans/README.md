# Planes de mejora — GenPic

Planes de implementación escritos en junio de 2026 tras una auditoría del
código, cuando GenPic estaba en producción (v1.4.0). Cada uno describe un
problema real, el estado del código en ese momento, los pasos, la
verificación y las condiciones para detenerse. Se publican como registro de
cómo se trabajó: todos están ejecutados.

Las rutas de los planes son las del repositorio original, donde la app vivía
en una carpeta `version-es/`. En este repo esa carpeta es la raíz: quita el
prefijo `version-es/` para encontrar cada fichero. Los hashes de commit son
también del repositorio original y no existen aquí.

## Planes

| Plan | Título | Prioridad | Esfuerzo | Depende de |
|------|--------|-----------|----------|------------|
| [001](001-reparar-ban-ip-ttl-carbon3.md) | Reparar el ban de IP (TTL con Carbon 3 + `catch \Throwable`) | P1 | S | — |
| [002](002-baseline-tests-y-ci.md) | Suite de tests en verde + CI en GitHub Actions | P1 | M | 001 |
| [003](003-ip-real-cloudflare-trustproxies.md) | TrustProxies limitado a Cloudflare: IP no falsificable | P1 | M | 002 |
| [004](004-fix-sendcampaignbatchjob-failed.md) | Fix de `SendCampaignBatchJob::failed()` | P2 | S | 002 |
| [005](005-tests-servicios-criticos.md) | Tests de caracterización: cupones, fulfillment, webhook firmado | P2 | M–L | 002 |
| [006](006-hardening-menor.md) | Hardening menor: rate limit unificado, `hash_equals`, `npm audit fix` | P3 | S | 002 |
| [007](007-fix-checkout-500-sin-product.md) | Fix del 500 en `/checkout` sin `?product=` | P1 | S | 002 |
| [008](008-enum-sending-mail-sends.md) | Añadir `sending` al enum `mail_sends.status` | P1 | S | 004 |
| [009](009-admin-visor-imagenes-privado.md) | Visor privado de imágenes en el admin | P2 | M | — |

Hubo dos arreglos más sin plan escrito: el 500 en el listado de bans por
`->default('—')` en una columna de fecha de Filament, y el streaming del admin
sin `fpassthru` (deshabilitada en el PHP de producción).

## Lecciones que dejaron

- **Nada de `config()` ni `env()` dentro de los closures de middleware de
  `bootstrap/app.php`.** Ese código corre antes de cargar la configuración: el
  plan 003 lo hizo y tumbó la web entera. Los tests no lo detectan porque
  arrancan por el kernel de consola; hay que probar el arranque HTTP real con
  `php public/index.php`.
- **En Filament, las columnas de fecha usan `->placeholder()`, nunca
  `->default()`** con un texto que Carbon no sepa parsear.
- **Si el hosting deshabilita `fpassthru`**, evita `Storage::response()` y usa
  `readStream` + `stream_copy_to_stream`.
- **Editar el `.env` no refresca la config cacheada**: después hace falta
  `php artisan config:cache`.

## Hallazgos descartados

(Para que nadie los vuelva a auditar. Cada cita se comprobó a mano contra el código.)

- **"SyncIpBansToRedis invierte diffInSeconds"** — falso positivo: con Carbon 3, `now()->diffInSeconds($futuro, false)` es positivo; el comando es CORRECTO. El bug real estaba en `IpBanService::syncToRedis()` (plan 001).
- **"Incremento de cupón fuera de transacción en OrderFulfillmentService"** — falso: está dentro de `DB::transaction` con `lockForUpdate` + guard de idempotencia (líneas 32–93).
- **"N+1 en tabla Filament GenerationSessionResource (latestGeneration)"** — falso: las referencias son entradas de Infolist (vista de detalle, 1 registro), no columnas de tabla.
- **"LandingController no usa CachedQueries"** — cierto pero irrelevante: las rutas de landing van detrás de `cache.page` (7 días); las queries solo corren en cache miss. No vale el cambio.
- **"No hay fallback multi-proveedor de IA"** — falso: `ImageGenerationService::generate()` itera la cadena con cooldowns, presupuesto diario y logging; está implementado.
- **"/my-downloads permite enumeración de emails sin límite"** — falso: `send-pin` tiene `throttle:3,5` y `verify` `throttle:5,1` (routes/web.php:193-195).
- **"IpBanService re-banea IPs desbaneadas por el admin"** — by design: el comentario en el código ("Resetear si fue desbaneado antes") muestra intención; una nueva violación tras un desbaneo debe re-banear.
- **claimFreeDownload TOCTOU** — mitigado en v1.3.0 (transacción + re-check); el residual es una order duplicada de 0€ en una carrera improbable. No vale plan.
- **Upgrade Laravel 11 → 13 y Filament 3 → 5** — diferido conscientemente: esfuerzo L, riesgo alto en un SaaS en producción de un solo mantenedor, sin incompatibilidades activas hoy. Re-evaluar cuando Laravel 11 se acerque a fin de fixes de seguridad o cuando un paquete lo exija. Prerequisito: plan 005 (red de tests).
- **Intervention Image v3 → v4** — diferido: misma lógica; requiere regresión visual del pipeline de imágenes.
- **Duplicación GeneratePortraitJob/GenerateUpsellPortraitJob y de las 10 landings Blade** — deuda real (confirmada) pero de leverage medio; abordar después del plan 005, cuando exista red de tests. Candidata a próxima ronda.
- **Reconciliación Stripe sin check de divisa** — implausible: el checkout se crea desde la propia order; la divisa no puede divergir en el flujo actual.
- **Token HuggingFace expuesto** — especulativo, sin evidencia en código git-tracked. Nada que hacer.
- **Cuadradas clasificadas como vertical en PhotoUploader (orientación)** — menor, impacto solo estético en el hint del prompt; anotado, sin plan.
- **`locale_route()` con fallback silencioso** — menor; un warning de log sería razonable, pero el coste/beneficio no justifica plan propio.

## Hallazgos de dirección (opciones de producto, no problemas)

1. **Endurecer el sistema de campañas antes de explotarlo** — el mailing a una base de suscriptores propia era la palanca de ingresos prevista; el bug del plan 004 demuestra que el camino de error no está probado. Tras 004, un test E2E de campaña completa daría confianza para enviar a volumen.
2. **Sincronización productos Stripe ↔ tabla local** — Esfuerzo ~M. Valor: reporting consistente sin re-consultar Stripe.
3. **Más idiomas (DE/NL)** — la arquitectura de slugs localizados ya soporta 5 locales (es/en/fr/pt/it); añadir alemán/neerlandés es mecánico (lang/ + slugs + sitemap) y abre mercados con buen poder adquisitivo para el producto regalo. Esfuerzo ~M por idioma, mayormente traducción.
