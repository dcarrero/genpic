# Plan 004: Arreglar SendCampaignBatchJob::failed() — propiedad $campaignId inexistente deja envíos atascados en 'sending'

## Status

- **Priority**: P2
- **Effort**: S
- **Risk**: LOW
- **Depends on**: plans/002-baseline-tests-y-ci.md (suite verde para validar)
- **Category**: bug
- **Planned at**: commit `61c6966`, 2026-06-11

## Why this matters

`SendCampaignBatchJob` reserva lotes de emails marcándolos `pending → sending` (lock atómico) y su `failed()` debe revertir `sending → pending` para que un job posterior los reintente. Pero `failed()` filtra por `$this->campaignId`, una propiedad que **no existe** en la clase (solo existe `$this->campaign`, el modelo). En PHP 8 leer una propiedad indefinida devuelve `null` con un warning, así que el `WHERE campaign_id = null` no matchea ninguna fila: tras un fallo del job (timeout SMTP, excepción a mitad de lote), los `MailSend` del lote quedan **permanentemente en `sending`** — ni se envían ni se reintentan, y el contador de la campaña queda inconsistente. Es exactamente la regresión del fix H8 de la auditoría de marzo (que introdujo el estado intermedio `sending` + recovery).

## Current state

- `version-es/app/Jobs/SendCampaignBatchJob.php:24-29` — constructor; las únicas propiedades son `$campaign` y `$batchSize`:

```php
public function __construct(
    public MailCampaign $campaign,
    public int $batchSize = 100,
) {
    $this->onQueue('default');
}
```

- `version-es/app/Jobs/SendCampaignBatchJob.php:154-164` — el bug:

```php
public function failed(\Throwable $exception): void
{
    MailSend::where('campaign_id', $this->campaignId)   // ← propiedad inexistente → null
        ->where('status', 'sending')
        ->update(['status' => 'pending']);

    Log::error("SendCampaignBatchJob failed — sends reverted to pending", [
        'campaign' => $this->campaignId,                 // ← ídem
        'error'    => $exception->getMessage(),
    ]);
}
```

- El job usa `SerializesModels`, así que en `failed()` el modelo `$this->campaign` está disponible (re-hidratado desde BD por su id).
- Modelos implicados: `version-es/app/Models/MailCampaign.php`, `version-es/app/Models/MailSend.php`. NO existen factories para ellos (`database/factories/` solo tiene ArtisticStyle, Format, Generation, GenerationSession, Order, OrderItem, Product, PurgeQueue, User).
- Convención de tests: `version-es/tests/Unit/Services/StorageServiceTest.php` como patrón estructural; los feature tests usan sqlite :memory: (RefreshDatabase disponible).

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Test nuevo | `php artisan test --filter=SendCampaignBatchJobTest` | pasa |
| Suite | `php artisan test` | exit 0 |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

## Scope

**In scope**:
- `version-es/app/Jobs/SendCampaignBatchJob.php` (solo el método `failed()`)
- `version-es/tests/Unit/Jobs/SendCampaignBatchJobTest.php` (crear; crear el directorio `tests/Unit/Jobs/` si no existe)
- `version-es/database/factories/MailCampaignFactory.php` y `MailSendFactory.php` (crear SOLO si el test las necesita; alternativa válida: `Model::create([...])` directo)

**Out of scope** (NO tocar):
- El método `handle()` del job (reserva atómica, contadores, usleep) — funciona; cualquier "mejora" aquí es scope creep.
- `app/Filament/Resources/MailCampaignResource*` y el flujo de campañas en admin.
- Otros jobs de email (`ProcessAbandonmentEmailsJob`, `ProcessUpgradeEmailsJob`).

## Git workflow

- Branch: `advisor/004-campaign-failed-revert`
- Commit estilo repo: `fix: SendCampaignBatchJob::failed() usaba propiedad inexistente — sends quedaban atascados en 'sending'`
- No push salvo instrucción del operador.

## Steps

### Step 1: Corregir `failed()`

Reemplazar las dos referencias a `$this->campaignId` por `$this->campaign->id`. Defensa adicional (el modelo podría haber sido borrado entre el fallo y la ejecución de `failed()`): envolver en un guard nulo-seguro. Forma objetivo:

```php
public function failed(\Throwable $exception): void
{
    $campaignId = $this->campaign?->id;

    if ($campaignId) {
        MailSend::where('campaign_id', $campaignId)
            ->where('status', 'sending')
            ->update(['status' => 'pending']);
    }

    Log::error("SendCampaignBatchJob failed — sends reverted to pending", [
        'campaign' => $campaignId,
        'error'    => $exception->getMessage(),
    ]);
}
```

**Verify**: `grep -n "campaignId" app/Jobs/SendCampaignBatchJob.php` → 0 resultados con `$this->campaignId`; `php -l app/Jobs/SendCampaignBatchJob.php` → sin errores.

### Step 2: Test que reproduce el bug

Crear `version-es/tests/Unit/Jobs/SendCampaignBatchJobTest.php` con `RefreshDatabase`. Caso principal:

1. Crear una `MailCampaign` (status `sending`) y 3 `MailSend` asociados con `status = 'sending'` + 1 `MailSend` de OTRA campaña también en `sending` (control de aislamiento).
2. Instanciar el job: `$job = new SendCampaignBatchJob($campaign);` y llamar `$job->failed(new \Exception('smtp timeout'));`.
3. Asertar: los 3 sends de la campaña vuelven a `pending`; el send de la otra campaña sigue en `sending`.

Para crear los modelos, inspeccionar `$fillable`/columnas en `app/Models/MailCampaign.php` y `app/Models/MailSend.php` y la migración correspondiente en `database/migrations/` para los campos NOT NULL mínimos. Si resulta más limpio, crear las dos factories (patrón: `database/factories/OrderFactory.php`).

**Verify**: `php artisan test --filter=SendCampaignBatchJobTest` → pasa. Comprobación de regresión: revertir temporalmente el Step 1 (git stash) y confirmar que el test FALLA; restaurar (git stash pop).

### Step 3: Suite completa

**Verify**: `php artisan test` → exit 0.

## Test plan

Cubierto en Step 2 (caso del bug + aislamiento entre campañas). Patrón estructural: `tests/Unit/Services/StorageServiceTest.php` (+ `RefreshDatabase` como en los feature tests existentes).

## Done criteria

- [ ] `grep -rn '\$this->campaignId' version-es/app/Jobs/` → 0 resultados
- [ ] `php artisan test --filter=SendCampaignBatchJobTest` → exit 0, y el test falla si se revierte el fix (verificado en Step 2)
- [ ] `php artisan test` → exit 0
- [ ] `git status` → sin cambios fuera del scope
- [ ] Fila actualizada en `plans/README.md`

## STOP conditions

- `failed()` ya no coincide con el extracto (posible fix independiente) — verificar si el bug persiste antes de tocar nada.
- Los modelos `MailCampaign`/`MailSend` no tienen las columnas asumidas (`status`, `campaign_id`) — leer las migraciones y reportar la discrepancia si el test no puede construirse tal cual.
- El test de regresión del Step 2 NO falla con el código original — significaría que el diagnóstico es erróneo; reportar.

## Maintenance notes

- Si se añade más estado al job (p. ej. tracking por lote), recordar que `failed()` corre en una instancia re-deserializada: solo las propiedades serializadas existen. Preferir derivar datos de `$this->campaign`.
- Vigilar en producción (Horizon) si hay `MailSend` antiguos atascados en `sending` de fallos previos a este fix: una vez desplegado, puede hacer falta un saneo puntual `UPDATE mail_sends SET status='pending' WHERE status='sending' AND updated_at < <fecha-deploy>` — decisión del operador, no forma parte del fix.
- Diferido: test de integración del `handle()` completo (reserva atómica, contadores) — entra en el plan 005/futuro, no aquí.
