# Plan 001: Reparar el sistema de ban de IP — TTL negativo (Carbon 3) y catch que no captura Errors

## Status

- **Priority**: P1
- **Effort**: S
- **Risk**: LOW
- **Depends on**: none
- **Category**: bug (con impacto de seguridad)
- **Planned at**: commit `61c6966`, 2026-06-11

## Why this matters

El sistema de moderación v1.4.0 banea IPs que suben contenido prohibido: escribe el ban en BD **y** en Redis, y el middleware global `CheckIpBan` consulta **solo Redis**. El bug: `IpBanService::syncToRedis()` calcula el TTL con `$ban->expires_at->diffInSeconds(now())`. El proyecto usa **Carbon 3.11.3** (ver `composer.lock`), donde `diffInSeconds()` devuelve un float **con signo** por defecto (cambio de comportamiento respecto a Carbon 2): `$a->diffInSeconds($b)` = `$b - $a`. Como `expires_at` está en el futuro, el resultado es **negativo**, el guard `if ($ttl > 0)` nunca pasa, y **el ban jamás llega a Redis**. Resultado: los bans en runtime no se aplican — un usuario baneado por contenido NSFW sigue navegando con normalidad. El comando manual `moderation:sync-bans` sí tiene la aritmética correcta, pero no está programado en el schedule, así que no compensa el bug.

Problema secundario en el mismo subsistema: `CheckIpBan` y `IpBanService` capturan `\Exception`, pero un fallo como `Class "Redis" not found` (extensión phpredis ausente) es un `\Error`, que **no** hereda de `\Exception`. El "fail-open" diseñado no funciona ante esa clase de fallos: hoy eso tumba 41 tests de la suite local y, en producción, un problema con la extensión tiraría el sitio entero (el middleware es global) en vez de degradar.

## Current state

- `version-es/app/Services/ContentModeration/IpBanService.php` — servicio de bans; el bug está en `syncToRedis()` (líneas 81–91):

```php
// version-es/app/Services/ContentModeration/IpBanService.php:81-91
private function syncToRedis(IpBan $ban): void
{
    try {
        $ttl = (int) $ban->expires_at->diffInSeconds(now());   // ← NEGATIVO con Carbon 3
        if ($ttl > 0) {
            Redis::setex(self::REDIS_PREFIX . $ban->ip_address, $ttl, '1');
        }
    } catch (\Exception $e) {
        Log::warning('IpBanService Redis sync failed', ['error' => $e->getMessage()]);
    }
}
```

- El patrón **correcto** ya existe en `version-es/app/Console/Commands/SyncIpBansToRedis.php:21`:

```php
$ttl = (int) now()->diffInSeconds($ban->expires_at, false);   // positivo si expires_at es futuro
```

- `version-es/app/Http/Middleware/CheckIpBan.php` — middleware global (prepend en `bootstrap/app.php`); el catch silencioso está en las líneas 26–41:

```php
// version-es/app/Http/Middleware/CheckIpBan.php:26-41
try {
    if (Redis::get('ip_ban:' . $request->ip())) {
        // ... respuesta 503 genérica
    }
} catch (\Exception $e) {
    // Fail-open: si Redis no disponible, no bloquear usuarios
}
```

- `IpBanService` tiene otros dos catch `\Exception` con el mismo problema: `isBanned()` (línea 20) y `removeFromRedis()` (línea 97).
- `version-es/routes/console.php` — schedule del proyecto (purga diaria, `stripe:sync-orders` cada 5 min...). `moderation:sync-bans` NO está programado.
- Convenciones del repo: comentarios en español, código/identificadores en inglés, logs con `Log::warning`/`Log::info` y contexto en array (ver los propios ficheros citados como exemplar).

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Tests (filtro) | `php artisan test --filter=IpBanServiceTest` | todos pasan |
| Tests (suite) | `php artisan test` | ver nota* |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

\* La suite completa hoy falla con `Class "Redis" not found` (41 tests). El paso 2 de este plan arregla la causa raíz; tras este plan la suite debería quedar verde o casi verde (lo remata el plan 002).

## Scope

**In scope** (únicos ficheros a modificar):
- `version-es/app/Services/ContentModeration/IpBanService.php`
- `version-es/app/Http/Middleware/CheckIpBan.php`
- `version-es/routes/console.php`
- `version-es/tests/Unit/Services/IpBanServiceTest.php` (crear)

**Out of scope** (NO tocar aunque parezcan relacionados):
- `version-es/app/Console/Commands/SyncIpBansToRedis.php` — su aritmética es CORRECTA; no "unificarla" cambiándola.
- `version-es/bootstrap/app.php` (TrustProxies) — lo cubre el plan 003.
- `version-es/app/Jobs/GeneratePortraitJob.php` y el resto del pipeline de moderación.
- `version-es/phpunit.xml` — lo cubre el plan 002 si hiciera falta.

## Git workflow

- Branch: `advisor/001-ban-ip-ttl`
- Commits estilo del repo (conventional commits en español), p. ej.: `fix: TTL de ban IP negativo con Carbon 3 — bans nunca llegaban a Redis`
- No hacer push ni abrir PR salvo instrucción explícita del operador.

## Steps

### Step 1: Corregir el TTL en `IpBanService::syncToRedis()`

En `version-es/app/Services/ContentModeration/IpBanService.php:84`, reemplazar:

```php
$ttl = (int) $ban->expires_at->diffInSeconds(now());
```

por el patrón ya probado del comando de sync:

```php
$ttl = (int) now()->diffInSeconds($ban->expires_at, false);
```

Añadir un comentario de una línea en español explicando la restricción (Carbon 3 devuelve diffs con signo; el orden de los argumentos importa).

**Verify**: `php -l app/Services/ContentModeration/IpBanService.php` → `No syntax errors`

### Step 2: Cambiar `catch (\Exception)` por `catch (\Throwable)` en el subsistema

1. `version-es/app/Http/Middleware/CheckIpBan.php:39` — cambiar a `catch (\Throwable $e)` y añadir dentro del catch un log para que el fail-open deje rastro:
   ```php
   Log::warning('CheckIpBan Redis lookup failed — fail-open', ['error' => $e->getMessage()]);
   ```
   (Añadir `use Illuminate\Support\Facades\Log;` al fichero.)
2. `version-es/app/Services/ContentModeration/IpBanService.php` — cambiar los tres `catch (\Exception $e)` (en `isBanned()`, `syncToRedis()`, `removeFromRedis()`) a `catch (\Throwable $e)`. Los logs ya existen; no tocarlos.

**Verify**: `grep -n "catch (\\\\Exception" app/Http/Middleware/CheckIpBan.php app/Services/ContentModeration/IpBanService.php` → sin resultados

### Step 3: Programar `moderation:sync-bans` en el schedule

En `version-es/routes/console.php`, junto a los demás `Schedule::command(...)`, añadir:

```php
// Re-sincronizar bans activos de BD a Redis — cinturón ante restarts de Redis
Schedule::command('moderation:sync-bans')->hourly();
```

**Verify**: `php artisan schedule:list 2>/dev/null | grep sync-bans` → muestra la entrada horaria. (Si `schedule:list` falla por falta de BD local, verificar con `grep -n "sync-bans" routes/console.php`.)

### Step 4: Tests unitarios que habrían cazado el bug

Crear `version-es/tests/Unit/Services/IpBanServiceTest.php`, siguiendo el estilo de `version-es/tests/Unit/Services/StorageServiceTest.php` (namespace `Tests\Unit\Services`, extiende `Tests\TestCase`). Usar `Redis::shouldReceive(...)` (mock del facade `Illuminate\Support\Facades\Redis`) y `RefreshDatabase` si el modelo `IpBan` necesita BD (sqlite :memory: ya configurado en phpunit.xml). Casos mínimos:

1. **`ban()` escribe en Redis con TTL positivo** (el caso del bug): mockear `Redis::shouldReceive('setex')->once()->withArgs(fn ($key, $ttl, $val) => $key === 'ip_ban:1.2.3.4' && $ttl > 0 && $ttl <= 72 * 3600 + 5)`. Llamar `$service->ban('1.2.3.4', 72)` y asertar que el `IpBan` queda en BD con `expires_at` ~72h en el futuro.
2. **`isBanned()` devuelve true/false según Redis**: `Redis::shouldReceive('get')->andReturn('1')` → true; `andReturn(null)` → false.
3. **`isBanned()` fail-open ante `\Error`**: `Redis::shouldReceive('get')->andThrow(new \Error('Class "Redis" not found'))` → devuelve `false` sin lanzar.
4. **`unban()` borra la clave**: `Redis::shouldReceive('del')->once()`; asertar `unbanned_at`/`unbanned_by` actualizados en BD.

Nota: no existe `IpBanFactory`; si los tests la necesitan, crear el `IpBan` con `IpBan::create([...])` directamente (mirar `version-es/app/Models/IpBan.php` para los `fillable`) en lugar de crear una factory.

**Verify**: `php artisan test --filter=IpBanServiceTest` → todos los tests pasan (≥4 tests)

### Step 5: Comprobar el efecto sobre la suite completa

Ejecutar la suite entera. El cambio del Step 2 (catch `\Throwable` en el middleware global) debería eliminar los fallos masivos por `Class "Redis" not found`.

**Verify**: `php artisan test` → 0 errores `Class "Redis" not found`. Si quedan tests en rojo por OTRAS causas, anotarlos en el informe final (los remata el plan 002) — no intentar arreglarlos aquí.

## Test plan

Cubierto en el Step 4. Patrón estructural: `tests/Unit/Services/StorageServiceTest.php`. Verificación: `php artisan test --filter=IpBanServiceTest` → verde.

## Done criteria

Machine-checkable. TODOS deben cumplirse:

- [ ] `grep -c "diffInSeconds" version-es/app/Services/ContentModeration/IpBanService.php` → la línea usa `now()->diffInSeconds($ban->expires_at, false)`
- [ ] `grep -rn "catch (\\\\Exception" version-es/app/Http/Middleware/CheckIpBan.php version-es/app/Services/ContentModeration/IpBanService.php` → 0 resultados
- [ ] `grep -n "sync-bans" version-es/routes/console.php` → 1 resultado
- [ ] `php artisan test --filter=IpBanServiceTest` → exit 0
- [ ] `php artisan test` → sin errores `Class "Redis" not found`
- [ ] `git status` → sin ficheros modificados fuera del scope
- [ ] Fila de estado actualizada en `plans/README.md`

## STOP conditions

Parar y reportar (no improvisar) si:

- Los extractos de "Current state" no coinciden con el código vivo (drift).
- `composer.lock` muestra `nesbot/carbon` < 3.0 — la premisa del bug (diff con signo) sería falsa; verificar manualmente con `php artisan tinker` antes de cambiar nada.
- El test del Step 4.1 falla porque `setex` recibe TTL negativo **después** del fix — indicaría otra causa; investigar, no parchear con `abs()`.
- Arreglar la suite (Step 5) parece requerir tocar `phpunit.xml` o `TestCase.php` — eso es del plan 002.

## Maintenance notes

- Cualquier código nuevo que calcule TTLs con Carbon debe usar el patrón `now()->diffInSeconds($futuro, false)`; con Carbon 3 los `diffIn*()` son con signo. Buscar usos nuevos en revisión de PRs.
- Si en el futuro se cambia el almacenamiento de bans (p. ej. a `Cache`), mantener la dualidad BD (persistencia/admin) + almacén rápido, y conservar los tests del Step 4.
- Revisor: comprobar que el log añadido en `CheckIpBan` no pueda inundar logs en producción si Redis cae (es 1 línea por request; aceptable a corto plazo, considerar throttling de logs si Redis cae a menudo).
- Deuda diferida adelante: `CheckIpBan` podría hacer fallback a BD cuando Redis falle (hoy fail-open puro). Decisión consciente: no se añade aquí para no meter una query de BD por request en el camino caliente.
