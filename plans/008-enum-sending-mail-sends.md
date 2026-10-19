# Plan 008: Añadir 'sending' al enum de mail_sends.status — el código lo escribe pero el schema no lo admite

## Status

- **Priority**: P1 (bloquea el sistema de campañas en producción)
- **Effort**: S
- **Risk**: LOW–MED (migración sobre tabla existente; sin pérdida de datos — solo amplía el enum)
- **Depends on**: plans/004-fix-sendcampaignbatchjob-failed.md (DONE — mergeado en `ddfc9e8`)
- **Category**: bug
- **Planned at**: commit `ddfc9e8`, 2026-06-12

## Why this matters

Descubierto durante la ejecución del plan 004: `SendCampaignBatchJob::handle()` reserva lotes con `UPDATE mail_sends SET status='sending'` (estado intermedio introducido en v1.3.0), y `failed()` revierte `'sending' → 'pending'`. Pero la migración define la columna como `enum('pending','sent','failed','bounced')` — **sin `'sending'`**. En MariaDB con strict mode (default de Laravel), ese UPDATE lanza error SQL: la reserva atómica de lotes falla y el sistema de campañas no puede enviar. En el mejor caso (strict off) escribiría cadena vacía, corrompiendo el estado. El fix: ampliar el enum. De paso, el test del plan 004 dejó un workaround (`PRAGMA ignore_check_constraints`) para poder insertar `'sending'` en sqlite — con el schema corregido, ese hack sobra y debe eliminarse para que el test valide el schema real.

## Current state

- `version-es/database/migrations/2026_03_25_000001_create_mailing_system.php:55` — la definición original (NO editar esta migración: ya está ejecutada en producción; se crea una nueva):

```php
$table->enum('status', ['pending', 'sent', 'failed', 'bounced'])->default('pending');
```

- (Contraste: `mail_campaigns.status` en la línea 35 SÍ incluye `'sending'` — el gap es solo en `mail_sends`.)
- `version-es/app/Jobs/SendCampaignBatchJob.php` — escribe `'sending'` en `handle()` (`->update(['status' => 'sending'])`, ~línea 60) y lo lee/revierte en `failed()`.
- `version-es/tests/Unit/Jobs/SendCampaignBatchJobTest.php:17-37` — el helper `createSendInSendingStatus()` usa `PRAGMA ignore_check_constraints = ON/OFF` para eludir el CHECK de sqlite. Tras este plan debe insertar `'sending'` directamente, sin PRAGMA.
- Los tests corren en sqlite `:memory:` (RefreshDatabase ejecuta TODAS las migraciones); producción es MariaDB 10.11.
- Laravel 11 con schema builder nativo (sin doctrine/dbal): `->change()` sobre columnas enum funciona en MySQL/MariaDB y en sqlite (recrea la tabla).

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Test del job | `php artisan test --filter=SendCampaignBatchJobTest` | pasa sin PRAGMA |
| Suite | `php artisan test` | exit 0 |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

(Nota: no hay MariaDB local — la validación MySQL-specific se hace por revisión del SQL y en el deploy; ver Step 1 y Maintenance notes.)

## Scope

**In scope**:
- `version-es/database/migrations/` — UNA migración nueva (crear)
- `version-es/tests/Unit/Jobs/SendCampaignBatchJobTest.php` (quitar el workaround PRAGMA)

**Out of scope** (NO tocar):
- `2026_03_25_000001_create_mailing_system.php` — migración histórica ya ejecutada; NUNCA editarla.
- `app/Jobs/SendCampaignBatchJob.php` — su lógica es correcta; el bug es del schema.
- Cualquier otro enum del proyecto.

## Git workflow

- Branch: `advisor/008-enum-sending`
- Commit estilo repo: `fix: mail_sends.status no admitía 'sending' — el enum del schema rompía la reserva de lotes`
- No push salvo instrucción del operador.

## Steps

### Step 1: Crear la migración

`php artisan make:migration add_sending_to_mail_sends_status_enum` y rellenar:

```php
public function up(): void
{
    if (DB::getDriverName() === 'sqlite') {
        // sqlite: ->change() recrea la tabla con el nuevo CHECK
        Schema::table('mail_sends', function (Blueprint $table) {
            $table->enum('status', ['pending', 'sending', 'sent', 'failed', 'bounced'])
                ->default('pending')->change();
        });

        return;
    }

    // MariaDB/MySQL: MODIFY directo — preserva datos, solo amplía el enum
    DB::statement("ALTER TABLE mail_sends MODIFY COLUMN status ENUM('pending', 'sending', 'sent', 'failed', 'bounced') NOT NULL DEFAULT 'pending'");

    // Saneo defensivo: si algún UPDATE previo dejó valores vacíos (strict off), recuperarlos
    DB::statement("UPDATE mail_sends SET status = 'pending' WHERE status = '' OR status IS NULL");
}

public function down(): void
{
    // Devolver posibles 'sending' a 'pending' antes de estrechar el enum
    DB::table('mail_sends')->where('status', 'sending')->update(['status' => 'pending']);

    if (DB::getDriverName() === 'sqlite') {
        Schema::table('mail_sends', function (Blueprint $table) {
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced'])
                ->default('pending')->change();
        });

        return;
    }

    DB::statement("ALTER TABLE mail_sends MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'bounced') NOT NULL DEFAULT 'pending'");
}
```

Ajustar imports (`DB`, `Schema`, `Blueprint`) y el `NOT NULL` si la columna original es nullable (comprobar en la migración original — el extracto de Current state muestra `->default('pending')` sin `nullable()`, así que NOT NULL es correcto).

**Verify**: `php artisan migrate:status 2>/dev/null | tail -3` o, si no hay BD local, `php -l database/migrations/<fichero>.php` → sin errores de sintaxis.

### Step 2: Quitar el workaround PRAGMA del test

En `version-es/tests/Unit/Jobs/SendCampaignBatchJobTest.php`, simplificar `createSendInSendingStatus()` eliminando las dos llamadas `DB::statement('PRAGMA ...')` y el comentario del hack — el insert con `'status' => 'sending'` debe funcionar directamente contra el schema migrado. Actualizar el docblock del helper para reflejar que el schema ya admite `'sending'`.

**Verify**: `php artisan test --filter=SendCampaignBatchJobTest` → pasa. Comprobación clave: `grep -c "PRAGMA" tests/Unit/Jobs/SendCampaignBatchJobTest.php` → 0. (Si el test falla con constraint violation aquí, la migración del Step 1 no se está aplicando en sqlite — revisar el branch del driver.)

### Step 3: Suite completa

**Verify**: `php artisan test` → exit 0.

## Test plan

El test existente del plan 004, ahora SIN eludir el schema, se convierte en la validación de la migración en sqlite: si la migración no añade `'sending'`, el insert del helper falla. No se necesitan tests adicionales.

## Done criteria

- [ ] Existe una nueva migración en `version-es/database/migrations/` que añade `'sending'` al enum (con rama sqlite y rama MySQL)
- [ ] `grep -rn "PRAGMA" version-es/tests/` → 0 resultados
- [ ] `php artisan test --filter=SendCampaignBatchJobTest` → exit 0
- [ ] `php artisan test` → exit 0
- [ ] La migración original `2026_03_25_000001_create_mailing_system.php` está intacta (`git diff -- version-es/database/migrations/2026_03_25_000001_create_mailing_system.php` → vacío)
- [ ] `git status` → sin cambios fuera del scope
- [ ] Fila actualizada en `plans/README.md`

## STOP conditions

- `->change()` sobre enum falla en sqlite con esta versión de Laravel — reportar el error exacto; la alternativa (recrear tabla a mano) debe aprobarla el revisor.
- El test del Step 2 falla por algo distinto a constraint/migración — reportar.
- Descubres que alguna OTRA columna enum tiene el mismo gap código/schema — NO arreglarla aquí; repórtala en NOTES.

## Maintenance notes

- **Despliegue (operador)**: tras el deploy hay que ejecutar `php artisan migrate` en producción (si el post-deploy script de RunCloud no lo hace ya). La migración es online-safe: `MODIFY ENUM` que amplía valores es metadata-only en MariaDB (instantáneo, sin reescritura de tabla).
- Tras migrar en producción, las campañas con sends atascados (si los hubo por el bug del plan 004 o errores SQL de este) pueden sanearse con: `UPDATE mail_sends SET status='pending' WHERE status='sending' AND updated_at < NOW() - INTERVAL 1 HOUR;` — decisión del operador.
- Patrón a vigilar en PRs futuros: cuando se añada un estado nuevo a una máquina de estados persistida como enum, el cambio de código DEBE ir acompañado de migración. (El audit ya recomendó tests de caracterización — plan 005 — que habrían cazado esto.)
