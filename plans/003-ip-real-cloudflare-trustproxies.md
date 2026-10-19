# Plan 003: Hacer no-spoofeable la IP del cliente — restringir TrustProxies a los rangos de Cloudflare

## Status

- **Priority**: P1
- **Effort**: M
- **Risk**: MED — una mala configuración haría que TODOS los visitantes compartan la IP del edge de Cloudflare, colapsando rate limits y bans. Los pasos incluyen un test que cubre ambos sentidos.
- **Depends on**: none (recomendado tras 001/002 para tener suite verde)
- **Category**: security
- **Planned at**: commit `61c6966`, 2026-06-11

## Why this matters

`bootstrap/app.php` confía en **cualquier** proxy (`trustProxies(at: '*')`) y honra `X-Forwarded-For`. Cloudflare **añade** la IP real del visitante al final de `X-Forwarded-For`, pero si el cliente envía su propio header `X-Forwarded-For: <ip-falsa>`, con `'*'` todos los saltos son "de confianza" y Laravel acaba devolviendo la IP **elegida por el atacante** en `$request->ip()`.

Cuando se aceptó `'*'` en la auditoría de marzo 2026, la IP solo afectaba al rate limiting. Desde v1.4.0 la IP es un control de seguridad en al menos 5 sitios (verificados en el audit):

1. `CheckIpBan` (`app/Http/Middleware/CheckIpBan.php:27`) — un baneado evade el ban con un header; o peor, se puede **inculpar a una IP ajena** subiendo contenido prohibido con `X-Forwarded-For` falsificado, baneando a un tercero.
2. `RestrictAdminByIp` (`app/Http/Middleware/RestrictAdminByIp.php:12`) — la allowlist de IPs del panel Filament se puede **suplantar** si el atacante conoce una IP admin.
3. `GenerationRateLimit` (`app/Http/Middleware/GenerationRateLimit.php:21`) — el límite de 3 generaciones gratis/IP/24h (coste real en proveedores IA) se evade rotando el header.
4. `PhotoUploader` (`app/Livewire/PhotoUploader.php:124`) — ídem.
5. `PortraitAccessController` — la clave `verified:{uuid}:{ip}` de verificación PIN depende de la IP.

El fix: confiar **solo** en los rangos publicados de Cloudflare (y loopback), de modo que Symfony descarte cualquier `X-Forwarded-For` inyectado por el cliente y se quede con la IP que reporta Cloudflare.

## Current state

- `version-es/bootstrap/app.php:14-20`:

```php
// Cloudflare: confiar en sus proxies para obtener IP real del visitante
$middleware->trustProxies(at: '*', headers:
    \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
    \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
    \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
    \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
);
```

- `version-es/config/genpic.php` — config propia del proyecto; ya tiene patrones de listas desde env, p. ej. `admin_allowed_ips` (línea 84) construida con `array_filter(array_map(...))` sobre un env CSV. Seguir ese patrón.
- Infraestructura (de la documentación del repo): origin en RunCloud/LiteSpeed detrás de Cloudflare (proxy naranja). El audit NO pudo verificar si el firewall del origin bloquea tráfico directo que no venga de Cloudflare — de ahí el Step 5.
- Laravel 11: `trustProxies(at: [...])` acepta un array de CIDRs.

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Tests | `php artisan test` | exit 0 |
| Test nuevo | `php artisan test --filter=TrustedProxiesTest` | pasa |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

## Scope

**In scope**:
- `version-es/bootstrap/app.php` (solo el bloque `trustProxies`)
- `version-es/config/genpic.php` (nueva clave `trusted_proxies`)
- `version-es/.env.example` (documentar la nueva variable opcional)
- `version-es/tests/Feature/TrustedProxiesTest.php` (crear)

**Out of scope** (NO tocar):
- Los consumidores de `$request->ip()` (CheckIpBan, RestrictAdminByIp, GenerationRateLimit, PhotoUploader, PortraitAccessController) — con la IP resuelta correctamente, ya quedan protegidos sin cambios.
- `trustHosts` y el resto de `bootstrap/app.php`.
- Cualquier cambio de infraestructura (firewall) — solo se documenta (Step 5).

## Git workflow

- Branch: `advisor/003-trustproxies-cloudflare`
- Commit estilo repo: `fix: TrustProxies restringido a rangos Cloudflare — IP de cliente no spoofeable`
- No push salvo instrucción del operador.

## Steps

### Step 1: Añadir los rangos de Cloudflare a la config

En `version-es/config/genpic.php`, añadir (cerca de `admin_allowed_ips`):

```php
// Proxies de confianza para resolver la IP real del visitante.
// Por defecto: rangos publicados de Cloudflare (https://www.cloudflare.com/ips/)
// + loopback. Sobreescribible por env TRUSTED_PROXIES (CSV) si CF cambia rangos.
'trusted_proxies' => array_filter(array_map('trim', explode(',', env(
    'TRUSTED_PROXIES',
    '173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,'
    . '141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,'
    . '197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,'
    . '104.24.0.0/14,172.64.0.0/13,131.0.72.0/22,'
    . '2400:cb00::/32,2606:4700::/32,2803:f800::/32,2405:b500::/32,'
    . '2405:8100::/32,2a06:98c0::/29,2c0f:f248::/32,'
    . '127.0.0.1,::1'
)))),
```

**IMPORTANTE**: antes de copiar la lista, contrastarla con https://www.cloudflare.com/ips-v4 y https://www.cloudflare.com/ips-v6 (o `curl -s https://www.cloudflare.com/ips-v4/`). Si difiere, usar la lista oficial vigente.

**Verify**: `php artisan tinker --execute="echo count(config('genpic.trusted_proxies'));"` → ≥ 20

### Step 2: Usar la config en bootstrap

En `version-es/bootstrap/app.php`, reemplazar `at: '*'` por la lista. `bootstrap/app.php` se ejecuta antes de cargar config de forma fiable vía helper en algunos contextos, así que usar `require` directo es frágil; en Laravel 11 el closure de `withMiddleware` se ejecuta con el contenedor disponible, pero la forma robusta y simple es leer el env/constante directamente desde config:

```php
$middleware->trustProxies(at: config('genpic.trusted_proxies'), headers:
    \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
    \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
    \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
    \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
);
```

Si `config()` no está disponible en ese punto (probarlo — ver Verify), mover la lectura a un array literal en `bootstrap/app.php` con comentario apuntando a `config/genpic.php`, o usar `env('TRUSTED_PROXIES', '<lista>')` directamente. Documentar en el commit cuál de las dos variantes se aplicó y por qué.

**Verify**: `php artisan config:clear && php artisan route:list > /dev/null && echo OK` → `OK` (el bootstrap no revienta). Después `php artisan test` → sin regresiones.

### Step 3: Test de no-spoofeabilidad

Crear `version-es/tests/Feature/TrustedProxiesTest.php` (patrón: cualquier feature test existente, p. ej. `tests/Feature/LandingTest.php`). Dos casos:

1. **Atacante directo no puede falsificar IP**: request con `REMOTE_ADDR` = IP pública NO-Cloudflare (p. ej. `203.0.113.50`) y header `X-Forwarded-For: 9.9.9.9`. Asertar `request()->ip() === '203.0.113.50'` (el header se ignora). Técnica: `$this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])->get('/', ['X-Forwarded-For' => '9.9.9.9'])` y capturar la IP vista, p. ej. registrando una ruta de test o asertando sobre `app('request')` tras el ciclo — alternativa más simple: definir en el test una ruta temporal `Route::get('/_test-ip', fn () => request()->ip())` vía `Route::` en el propio test y asertar el body.
2. **Tras Cloudflare se honra XFF**: `REMOTE_ADDR` = IP dentro de un rango CF (p. ej. `173.245.48.10`), header `X-Forwarded-For: 81.40.20.10` → la IP vista es `81.40.20.10`.

**Verify**: `php artisan test --filter=TrustedProxiesTest` → 2 tests pasan

### Step 4: Documentar la variable en .env.example

Añadir a `version-es/.env.example`:

```
# CSV de CIDRs de proxies de confianza. Vacío = usa rangos Cloudflare por defecto (config/genpic.php)
# TRUSTED_PROXIES=
```

**Verify**: `grep -n "TRUSTED_PROXIES" version-es/.env.example` → 1 resultado

### Step 5: Nota de despliegue (sin ejecutar nada)

Añadir al final de este fichero de plan (sección Maintenance notes ya la incluye), la checklist de despliegue para el operador:

1. Verificar en RunCloud/firewall que el origin **solo** acepta tráfico de los rangos de Cloudflare en 80/443 (si no, un atacante directo al origin presentaría su REMOTE_ADDR real — correcto — pero también podría saltarse el WAF de CF; recomendación de hardening, no bloqueo de este plan).
2. Tras desplegar, comprobar en logs/Telescope que las IPs registradas son variadas (de visitantes), no `172.x/104.x` de Cloudflare. Si TODAS las requests muestran IPs de rangos Cloudflare, la lista de trusted proxies no se está aplicando (rollback inmediato: restaurar `at: '*'` y reportar).

## Test plan

Cubierto en Step 3 (los dos sentidos: anti-spoof y pass-through legítimo). Verificación: `php artisan test` completo en verde.

## Done criteria

- [ ] `grep -n "at: '\*'" version-es/bootstrap/app.php` → 0 resultados
- [ ] `php artisan test --filter=TrustedProxiesTest` → exit 0, 2 tests
- [ ] `php artisan test` → exit 0
- [ ] `version-es/.env.example` documenta `TRUSTED_PROXIES`
- [ ] `git status` → sin cambios fuera del scope
- [ ] Fila actualizada en `plans/README.md` + checklist de despliegue del Step 5 incluida en el informe final

## STOP conditions

- `config()` no disponible en `bootstrap/app.php` Y la variante con `env()` directo tampoco supera el Verify del Step 2 — reportar en vez de inventar un tercer mecanismo.
- El Step 3.2 falla (la IP tras CF no se resuelve al visitante): indicaría que Cloudflare en esta instalación usa otro header (p. ej. solo `CF-Connecting-IP`); reportar — la alternativa (middleware propio que copie `CF-Connecting-IP` sobre la request) es un cambio de diseño que debe aprobar el operador.
- La suite (plan 002) no está verde antes de empezar — sin baseline no se puede validar la no-regresión.

## Maintenance notes

- Los rangos de Cloudflare cambian raramente pero cambian: revisar https://www.cloudflare.com/ips/ al menos una vez al año o suscribirse a su changelog. La variable `TRUSTED_PROXIES` permite reaccionar sin deploy de código.
- Si algún día el sitio deja de estar detrás de Cloudflare, esta lista DEBE actualizarse a la del nuevo proxy/balanceador, o todas las IPs de visitantes aparecerán como la IP del proxy.
- Revisor del PR: vigilar que no se "simplifique" de vuelta a `'*'` en futuros cambios de bootstrap.
- Diferido conscientemente: rate limit secundario por session-UUID además de por IP (defensa en profundidad si CF cambia de esquema); no bloquea este plan.
