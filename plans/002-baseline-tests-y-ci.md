# Plan 002: Dejar la suite de tests verde en local y añadir CI en GitHub Actions

## Status

- **Priority**: P1
- **Effort**: M
- **Risk**: LOW
- **Depends on**: plans/001-reparar-ban-ip-ttl-carbon3.md
- **Category**: tests / dx
- **Planned at**: commit `61c6966`, 2026-06-11

## Why this matters

A fecha del audit, `php artisan test` falla en local: 41 de ~90 tests revientan con `Class "Redis" not found` porque el middleware global `CheckIpBan` invoca el facade `Redis` en cada request de los feature tests, y la extensión phpredis no está instalada en la máquina de desarrollo. Sin una suite ejecutable no hay red de seguridad para NINGÚN cambio — incluido el resto de planes de este directorio. Además el repo no tiene ningún CI (`.github/workflows/` no existe), así que nada verifica los pushes a `main`/`deploy`. Este plan restaura la línea base de verificación y la blinda con un workflow de GitHub Actions.

## Current state

- El plan 001 cambia `CheckIpBan` para capturar `\Throwable` (fail-open real), lo que debería eliminar la mayoría de los 41 fallos. Este plan parte de ahí.
- `version-es/phpunit.xml` ya aísla los tests: sqlite `:memory:`, `CACHE_STORE=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`. No configura nada de Redis.
- Otros puntos del código que llaman a `Redis::` directamente y pueden seguir rompiendo tests que ejerciten esos flujos (verificado con grep en el audit):
  - `version-es/app/Http/Controllers/PortraitAccessController.php` — flujo PIN (claves `pin:{uuid}`, `pin_attempts:{uuid}`, `verified:{uuid}:{ip}`)
  - `version-es/app/Http/Controllers/MyDownloadsController.php` — flujo PIN de descargas
  - `version-es/app/Http/Middleware/GenerationRateLimit.php` — rate limit en `POST /upload`
  - `version-es/app/Services/ContentModeration/IpBanService.php` — ya con mocks en sus tests tras el plan 001
- Tests existentes: `version-es/tests/Feature/` (Download, Landing, StripeE2E, Schedule, Styles, StripeWebhook, GenerationFlow, PaymentFlow, LegalPages, PurgeExpiredFilesJob) y `version-es/tests/Unit/`.
- Remote de GitHub: GitHub → GitHub Actions disponible.
- `composer.json` (require-dev) incluye `laravel/pint`, pero no hay `pint.json` ni ningún hook/CI que lo ejecute. El binario funciona vía `php vendor/bin/pint`.
- El deploy real es vía RunCloud (push a `main` + branch `deploy` con subtree split) — el CI es solo verificación, NO despliega.

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Tests | `php artisan test` | exit 0, 0 failures |
| Test individual | `php artisan test --filter=NombreTest` | pasa |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |
| Validar workflow | `gh workflow list` (tras push) o YAML lint local | workflow listado |

## Scope

**In scope**:
- `version-es/phpunit.xml` (solo si hace falta una env var adicional)
- `version-es/tests/**` (ajustes de tests que aún fallen + `tests/TestCase.php` si se necesita un helper de mock de Redis)
- `.github/workflows/tests.yml` (crear, en la RAÍZ del repo, no en version-es/)
- `version-es/README.md` — añadir UNA sección breve "Tests" (cómo ejecutar, que no requiere Redis)

**Out of scope** (NO tocar):
- Cualquier fichero de `version-es/app/` — si un test solo puede arreglarse tocando código de producción, eso es un STOP (repórtalo como hallazgo).
- `pint.json` / reformatear el código — deuda aparte, no mezclarla aquí.
- Pasos de deploy en el CI — el deploy es de RunCloud; el workflow solo testea.

## Git workflow

- Branch: `advisor/002-baseline-ci`
- Commits: `fix: ...` / `feat: CI — tests en GitHub Actions` (estilo del repo, en español)
- No push salvo instrucción del operador (el workflow solo se activará cuando se pushee).

## Steps

### Step 1: Ejecutar la suite y triar lo que quede en rojo

`php artisan test` (con el plan 001 ya aplicado). Clasificar cada fallo restante:

- **Fallo por `Redis` en flujo PIN / rate limit** → arreglar EN EL TEST mockeando el facade: `Redis::shouldReceive('get')->andReturn(null);` etc. (patrón del plan 001, Step 4). Si muchos tests lo necesitan, añadir un helper `protected function fakeRedis(): void` en `version-es/tests/TestCase.php` que registre los `shouldReceive` habituales (`get` → null, `setex`/`incr`/`del`/`expire` → true) y llamarlo desde los tests afectados.
- **Fallo de aserción de negocio** (no de infraestructura) → NO arreglar el código de producción: documentarlo en el informe final como hallazgo y marcar el test con `$this->markTestIncomplete('Ver plans/README.md — hallazgo NNN')` SOLO si bloquea el verde; preferible arreglar la aserción si el test estaba mal escrito.

**Verify**: `php artisan test` → exit 0

### Step 2: Documentar cómo se ejecutan los tests

En `version-es/README.md` añadir una sección `## Tests` (4–6 líneas): comando `php artisan test`, que phpunit.xml usa sqlite :memory: + array drivers, que NO hace falta Redis ni MariaDB en local, y el filtro `--filter=NombreTest`.

**Verify**: `grep -n "php artisan test" version-es/README.md` → ≥1 resultado

### Step 3: Crear el workflow de CI

Crear `.github/workflows/tests.yml` (raíz del repo):

```yaml
name: tests

on:
  push:
    branches: [main]
  pull_request:

jobs:
  phpunit:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: version-es
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, intl, sqlite3, gd
          coverage: none
      - run: composer install --prefer-dist --no-progress --no-interaction
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci && npm run build
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan test
```

> Lección de la ejecución del plan 001 (checkout limpio): sin `public/build/manifest.json`,
> 21 feature tests fallan con `ViteManifestNotFoundException` — por eso el workflow
> compila los assets antes de testear. Alternativa más rápida si se prefiere: añadir
> `$this->withoutVite()` en `tests/TestCase.php::setUp()` y eliminar el paso de node;
> elegir UNA de las dos (preferida: `withoutVite()`, hace los tests independientes del front).

Notas de ejecución:
- NO añadir la extensión `redis` al setup: la suite debe pasar sin ella (esa es precisamente la garantía que protege este workflow).
- Si algún test requiere `imagick` (jobs de imagen), añadir `imagick` a `extensions`; comprobarlo solo si el run falla por eso.
- El repo usa `version-es/` como app Laravel — de ahí el `working-directory`.

**Verify**: localmente, validar el YAML (`ruby -ryaml -e 'YAML.load_file(".github/workflows/tests.yml")'` o un linter equivalente disponible) → sin errores de parseo. La verificación real (run verde) ocurrirá en el primer push; anotarlo en el informe.

### Step 4: Simular el CI en local

Reproducir los pasos del workflow en local desde un estado limpio razonable:

```bash
cd version-es && php artisan config:clear && php artisan test
```

**Verify**: exit 0.

## Test plan

Este plan ES el plan de tests (baseline). No se escriben tests de negocio nuevos aquí (eso es el plan 005); solo se reparan/mockean los existentes.

## Done criteria

- [ ] `cd version-es && php artisan test` → exit 0, 0 failures
- [ ] Ningún test usa `markTestIncomplete` sin referencia a un hallazgo documentado
- [ ] `.github/workflows/tests.yml` existe y parsea como YAML válido
- [ ] `git status` → sin cambios fuera del scope (en particular, NADA en `version-es/app/`)
- [ ] `version-es/README.md` documenta cómo ejecutar los tests
- [ ] Fila de estado actualizada en `plans/README.md`

## STOP conditions

- El plan 001 no está aplicado (el drift check no muestra sus cambios y la suite sigue llena de `Class "Redis" not found`).
- Arreglar un test requiere modificar código de producción en `version-es/app/` — reportar el fichero y el porqué.
- Más de ~10 tests fallan por causas de negocio (no de infraestructura) — la baseline tiene un problema más profundo; reportar la lista completa en vez de parchear.

## Maintenance notes

- A partir de ahora, cualquier middleware global nuevo que toque servicios externos (Redis, HTTP) debe tener fail-open con `\Throwable` o un fake en `tests/TestCase.php`, o volverá a romper la suite entera.
- Cuando se añada `pint.json` (deuda diferida), añadir un job `pint --test` al workflow.
- El workflow corre en pushes a `main` y PRs; el branch `deploy` (subtree) no se testea — es un artefacto derivado de `main`, no hace falta.
