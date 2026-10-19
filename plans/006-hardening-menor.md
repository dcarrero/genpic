# Plan 006: Hardening menor — unificar el rate limit de generaciones, hash_equals en el PIN y npm audit fix

## Status

- **Priority**: P3
- **Effort**: S
- **Risk**: LOW
- **Depends on**: plans/002-baseline-tests-y-ci.md (suite verde)
- **Category**: security / bug
- **Planned at**: commit `61c6966`, 2026-06-11

## Why this matters

Tres fixes pequeños, independientes entre sí, agrupados por tamaño:

1. **Doble contador de rate limit**: la regla de negocio es "3 generaciones gratuitas/IP/24h", pero hay DOS contadores con claves distintas: el componente Livewire `PhotoUploader` usa `RateLimiter` de Laravel con clave `generation:{ip}`, y el middleware `GenerationRateLimit` (aplicado a `POST /upload`, la ruta no-Livewire) usa Redis crudo con clave `generation_rate_limit:{ip}`. Un usuario que combine ambas vías obtiene hasta 6 generaciones — el doble del límite, con coste real por generación en el proveedor de IA.
2. **Comparación de PIN no constante**: `PortraitAccessController::verifyPin()` compara con `!==`. El riesgo práctico es bajo (límite de 5 intentos), pero `hash_equals()` es el estándar y el cambio es de una línea.
3. **Vulnerabilidades npm**: `npm audit` reporta 7 (2 critical, 3 high), todas con fix disponible vía `npm audit fix` (sin `--force`). Incluye axios (resuelto hoy a 1.13.6, con advisories abiertos) y vite (arbitrary file read del dev server — solo afecta a desarrollo, pero el fix es gratis).

## Current state

### Rate limit duplicado

- `version-es/app/Livewire/PhotoUploader.php:123-131` — chequeo; el `hit()` está en la línea 227 (solo tras encolar generación con éxito):

```php
$key = 'generation:' . request()->ip();
$max = config('genpic.rate_limit.generations_per_ip', 3);
$decay = config('genpic.rate_limit.period_hours', 24) * 3600;

if (RateLimiter::tooManyAttempts($key, $max)) {
    $this->error = __('ui.rate_limit');
    return;
}
// ... línea 227: RateLimiter::hit($key, $decay);
```

- `version-es/app/Http/Middleware/GenerationRateLimit.php:15-40` — Redis crudo con clave distinta:

```php
$key = 'generation_rate_limit:' . $request->ip();
$current = Redis::get($key);
if ($current === null) {
    Redis::setex($key, $periodSeconds, 1);
} elseif ((int) $current >= $maxAttempts) {
    // 429 con horas restantes
} else {
    Redis::incr($key);
}
```

- Registro y uso del middleware: alias `generation.limit` en `version-es/bootstrap/app.php:44`; aplicado en `version-es/routes/web.php:168` → `Route::post('/upload', [GenerationController::class, 'upload'])->middleware('generation.limit')`.
- La config compartida ya existe: `config/genpic.php:44-47` (`generations_per_ip`, `period_hours`).

### PIN

- `version-es/app/Http/Controllers/PortraitAccessController.php:138-147`:

```php
[$storedPin, $storedEmail] = explode(':', $stored, 2);

if ($request->pin !== $storedPin) {
    Redis::incr($attemptsKey);
    // ...
}
```

### npm

- `version-es/package.json`: `"axios": "^1.7.4"`; lockfile resuelve axios `1.13.6`.
- `npm audit` (2026-06-11): `7 vulnerabilities (2 moderate, 3 high, 2 critical)`, todas "fix available via npm audit fix" (sin force). El build usa Vite (`npm run build`).

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Tests | `php artisan test` | exit 0 |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |
| Audit npm | `npm audit` | 0 critical/high tras el fix |
| Build front | `npm run build` | exit 0, manifest generado |

## Scope

**In scope**:
- `version-es/app/Http/Middleware/GenerationRateLimit.php`
- `version-es/app/Http/Controllers/PortraitAccessController.php` (solo la comparación del PIN)
- `version-es/package.json` / `version-es/package-lock.json` (vía `npm audit fix`)
- `version-es/tests/Unit/Middleware/GenerationRateLimitTest.php` (crear)

**Out of scope** (NO tocar):
- `version-es/app/Livewire/PhotoUploader.php` — su implementación con `RateLimiter` es la canónica; se adapta el middleware a ella, no al revés.
- `npm audit fix --force` — PROHIBIDO (puede saltar majors de Vite/Tailwind y romper el build).
- La ruta `POST /upload` y `GenerationController` — no cambiar su comportamiento, solo el contador que la protege.
- `MyDownloadsController` — usa otro mecanismo de PIN; no hay comparación `!==` que cambiar (verificado en el audit).

## Git workflow

- Branch: `advisor/006-hardening-menor`
- Tres commits separados: `fix: unificar rate limit de generaciones en una sola clave`, `fix: hash_equals en verificación de PIN`, `chore: npm audit fix — 7 vulnerabilidades`
- No push salvo instrucción del operador.

## Steps

### Step 1: Unificar el contador del middleware con el de Livewire

Reescribir `GenerationRateLimit::handle()` para usar el `RateLimiter` de Laravel con la MISMA clave que `PhotoUploader` (`'generation:' . $request->ip()`), eliminando el acceso crudo a Redis. Forma objetivo:

```php
use Illuminate\Support\Facades\RateLimiter;

public function handle(Request $request, Closure $next): Response
{
    $max = config('genpic.rate_limit.generations_per_ip', 3);
    $decay = config('genpic.rate_limit.period_hours', 24) * 3600;
    $key = 'generation:' . $request->ip();   // misma clave que PhotoUploader

    if (RateLimiter::tooManyAttempts($key, $max)) {
        $horasRestantes = (int) ceil(RateLimiter::availableIn($key) / 3600);
        return response()->json([
            'error' => 'Has alcanzado el límite de generaciones gratuitas. Vuelve en ' . $horasRestantes . ' hora(s).',
            'retry_after' => RateLimiter::availableIn($key),
        ], 429);
    }

    RateLimiter::hit($key, $decay);
    return $next($request);
}
```

Matices a respetar:
- El middleware hace `hit()` ANTES de procesar (cuenta el intento); `PhotoUploader` hace `hit()` solo tras éxito (línea 227). Es una asimetría aceptada — no "arreglarla" tocando PhotoUploader.
- Mantener el formato de respuesta JSON 429 (mensaje + `retry_after`) — algún cliente puede depender de él.
- Eliminar el `use ... Redis` que queda huérfano.

**Verify**: `grep -n "generation_rate_limit" -r app/` → 0 resultados; `php -l app/Http/Middleware/GenerationRateLimit.php` → OK

### Step 2: Test del middleware unificado

Crear `version-es/tests/Unit/Middleware/GenerationRateLimitTest.php` (feature-style con rutas; patrón general: tests Feature existentes). Casos:

1. Con `RateLimiter` ya al límite para `generation:{ip}` (provocarlo con `RateLimiter::hit()` en el test, 3 veces), un POST a `/upload` → 429.
2. Sin intentos previos → el middleware deja pasar (la request llegará al controller; basta asertar que NO es 429 — un 422 de validación del controller es aceptable como prueba de paso).
3. Cross-check de unificación: tras 3 hits hechos con la clave de `PhotoUploader` (`generation:{ip}`), el middleware también bloquea — este caso es el que demuestra que ya no hay doble cupo.

Nota: `RateLimiter` usa el cache store (`array` en tests) — no necesita Redis.

**Verify**: `php artisan test --filter=GenerationRateLimitTest` → 3 tests pasan

### Step 3: hash_equals en el PIN

En `version-es/app/Http/Controllers/PortraitAccessController.php:140`, cambiar:

```php
if ($request->pin !== $storedPin) {
```

por:

```php
if (!hash_equals($storedPin, (string) $request->pin)) {
```

(`hash_equals` exige strings; el primer argumento es el valor conocido/secreto.)

**Verify**: `php artisan test` → los tests de flujo PIN existentes siguen verdes (si la suite del plan 002 los dejó verdes); `grep -n "hash_equals" app/Http/Controllers/PortraitAccessController.php` → 1 resultado

### Step 4: npm audit fix

```bash
npm audit fix          # SIN --force
npm audit
npm run build
```

Si tras `npm audit fix` quedan vulnerabilidades que requieren `--force` (cambio de major), NO aplicarlas: listar cuáles quedan y en qué paquete en el informe final.

**Verify**: `npm audit` → 0 critical y 0 high (o lista justificada de remanentes que exigen major); `npm run build` → exit 0

### Step 5: Suite completa

**Verify**: `php artisan test` → exit 0

## Test plan

Cubierto en Step 2 (el caso 3 es el test de regresión específico de la unificación). El cambio de `hash_equals` queda cubierto por los tests de flujo PIN existentes (comportamiento idéntico para PINs correctos/incorrectos).

## Done criteria

- [ ] `grep -rn "generation_rate_limit" version-es/app/` → 0 resultados
- [ ] `php artisan test --filter=GenerationRateLimitTest` → exit 0 (3 tests)
- [ ] `grep -c "hash_equals" version-es/app/Http/Controllers/PortraitAccessController.php` → 1
- [ ] `npm audit` → 0 critical/high (o remanentes justificados por major en el informe)
- [ ] `npm run build` → exit 0
- [ ] `php artisan test` → exit 0
- [ ] `git status` → sin cambios fuera del scope
- [ ] Fila actualizada en `plans/README.md`

## STOP conditions

- `POST /upload` resulta tener OTRO flujo de conteo adicional no descrito aquí (p. ej. el controller también cuenta) — reportar antes de unificar.
- `npm audit fix` modifica majors en `package-lock.json` (revisar el diff del lockfile: si `vite` o `tailwindcss` cambian de major, revertir y reportar).
- `npm run build` falla tras el audit fix — revertir lockfile (`git checkout -- package-lock.json package.json && npm install`) y reportar qué paquete lo causa.
- Los tests del flujo PIN fallan tras el Step 3 — `hash_equals` es comportamentalmente idéntico para strings; un fallo indica otro problema (p. ej. PIN no-string); reportar.

## Maintenance notes

- A partir de ahora hay UNA clave de rate limit de generaciones (`generation:{ip}`). Cualquier endpoint nuevo que dispare generaciones debe usar `RateLimiter` con esa misma clave — no inventar otra.
- Nota: el rate limit por IP depende del plan 003 (TrustProxies) para ser efectivo contra atacantes; sin él, la IP es falsificable.
- Tras el plan 003 + este, conviene vigilar los logs unos días: usuarios legítimos detrás de CGNAT comparten IP y el límite de 3/24h compartido puede generar 429 inocentes (era igual antes; ahora simplemente es consistente).
- npm: programar `npm audit` periódico (entra gratis si se añade al workflow de CI del plan 002 como job no-bloqueante).
