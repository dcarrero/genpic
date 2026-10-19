# Plan 007: Arreglar el 500 de /checkout/{uuid} sin ?product= (TypeError en productByKey)

## Status

- **Priority**: P1
- **Effort**: S
- **Risk**: LOW
- **Depends on**: plans/002-baseline-tests-y-ci.md (DONE — mergeado en `485132c`)
- **Category**: bug
- **Planned at**: commit `485132c`, 2026-06-12

## Why this matters

Descubierto al restaurar la baseline de tests (plan 002): la vista de checkout llama `CachedQueries::productByKey(request('product'))`. Sin query param `?product=`, `request('product')` devuelve `null`, y `productByKey(string $key)` tiene type hint **no nullable** → `TypeError` → **500 en la página de pago** para cualquier visita directa a `/checkout/{uuid}` (marcador del navegador, enlace de email sin parámetro, usuario que recarga tras quitar el param). Es la página de la que sale el 100% de los ingresos. El test `PaymentFlowTest::test_checkout_page_loads` está marcado `markTestIncomplete` con referencia a este bug — este plan lo restaura como test de regresión.

## Current state

- `version-es/resources/views/checkout/index.blade.php:4-8` — el bug en el bloque `@php`:

```php
@php
    $locale = app()->getLocale();
    $product = \App\Services\CachedQueries::productByKey(request('product')) ?? \App\Services\CachedQueries::activeProducts()->first();
    $name = $product->t('name');
    ...
@endphp
```

- `version-es/app/Services/CachedQueries.php:42-47` — la firma del método (NO modificarla; el fix va en la vista):

```php
public static function productByKey(string $key): ?Product
{
    return Cache::remember("q:product:{$key}", 604800, function () use ($key) {
        return Product::where('product_key', $key)->first();
    });
}
```

- `version-es/tests/Feature/PaymentFlowTest.php:20-31` — el test neutralizado por el plan 002:

```php
public function test_checkout_page_loads(): void
{
    // BUG real (documentado en plan 002 NOTES): ...
    $this->markTestIncomplete(
        'Bug real: checkout/index.blade.php pasa null a CachedQueries::productByKey(string). ...'
    );
}
```

- Es el ÚNICO call site de `productByKey` en vistas/controllers (verificado con grep el 2026-06-12).
- La suite de tests está verde tras el plan 002: `php artisan test` → 0 failures, 1 incomplete (este).

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Test del fix | `php artisan test --filter=PaymentFlowTest` | todos pasan, 0 incomplete |
| Suite | `php artisan test` | exit 0, 0 incomplete |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

## Scope

**In scope**:
- `version-es/resources/views/checkout/index.blade.php` (solo la línea del `$product`)
- `version-es/tests/Feature/PaymentFlowTest.php` (restaurar `test_checkout_page_loads` + añadir caso sin param)

**Out of scope** (NO tocar):
- `version-es/app/Services/CachedQueries.php` — cambiar la firma a `?string` afectaría a la clave de caché y a otros usos; el fix correcto es no pasar null desde la vista.
- El resto de la vista de checkout (precios, Stripe, etc.).

## Git workflow

- Branch: `advisor/007-checkout-500`
- Commit estilo repo: `fix: 500 en /checkout sin ?product= — null en productByKey(string)`
- No push salvo instrucción del operador.

## Steps

### Step 1: Fix en la vista

En `version-es/resources/views/checkout/index.blade.php`, reemplazar la línea del `$product` por una versión null-safe que solo consulte por clave cuando el param existe:

```php
$productKey = request('product');
$product = ($productKey ? \App\Services\CachedQueries::productByKey($productKey) : null)
    ?? \App\Services\CachedQueries::activeProducts()->first();
```

(Evita llamar `productByKey('')`, que contaminaría la caché con una clave vacía.)

**Verify**: `php -l` no aplica a blade; verificar con el test del Step 2.

### Step 2: Restaurar y ampliar el test de regresión

En `version-es/tests/Feature/PaymentFlowTest.php`, sustituir el `markTestIncomplete` restaurando la aserción original y añadiendo el caso del bug:

```php
public function test_checkout_page_loads(): void
{
    $session = GenerationSession::factory()->create();
    Product::factory()->create();

    // Caso del bug: sin ?product= no debe dar 500 (regresión del TypeError con null)
    $this->get("/checkout/{$session->uuid}")->assertStatus(200);

    // Con ?product= válido también carga
    $product = Product::first();
    $this->get("/checkout/{$session->uuid}?product={$product->product_key}")->assertStatus(200);

    // Con ?product= inválido cae al primer producto activo, no 500
    $this->get("/checkout/{$session->uuid}?product=no-existe")->assertStatus(200);
}
```

Ajustar a los nombres reales de columnas/factory si difieren (mirar `database/factories/ProductFactory.php`); si `GenerationSession` necesita estado extra para renderizar checkout (p. ej. generación asociada), montar lo mínimo copiando de otro test del mismo fichero que sí pase.

**Verify**: `php artisan test --filter=PaymentFlowTest` → todos pasan, 0 incomplete. Comprobación de regresión: revertir temporalmente el Step 1 (`git stash`) → el test DEBE fallar con el TypeError; restaurar (`git stash pop`).

### Step 3: Suite completa

**Verify**: `php artisan test` → exit 0, **0 incomplete** (el marcador del plan 002 ha desaparecido).

## Test plan

Cubierto en Step 2: los tres estados del query param (ausente / válido / inválido). Patrón: los demás tests de `PaymentFlowTest`.

## Done criteria

- [ ] `grep -n "request('product')" version-es/resources/views/checkout/index.blade.php` → la línea ya no pasa el valor sin guard de null
- [ ] `php artisan test --filter=PaymentFlowTest` → exit 0, 0 incomplete
- [ ] `grep -c "markTestIncomplete" version-es/tests/Feature/PaymentFlowTest.php` → 0
- [ ] `php artisan test` → exit 0, 0 incomplete
- [ ] `git status` → sin cambios fuera del scope
- [ ] Fila actualizada en `plans/README.md`

## STOP conditions

- El test del caso "sin ?product=" sigue dando 500 tras el fix — la causa es otra (p. ej. no hay producto activo en el seed del test); investigar el stack trace y reportar si no es el TypeError descrito.
- El fix parece requerir cambiar `CachedQueries::productByKey()` — está out of scope; reportar.
- La verificación de regresión del Step 2 NO falla con el código original — el diagnóstico sería erróneo; reportar.

## Maintenance notes

- Regla general que este bug ilustra: no pasar `request('x')` directamente a funciones con type hints no nullables — guard o default siempre. Vigilarlo en revisión de PRs (las vistas blade no pasan por análisis estático).
- Si algún día se añade larastan/phpstan (deuda diferida en el audit), este patrón en vistas seguiría sin detectarse — los blade compilados no suelen analizarse; la cobertura de tests de página es la red real.
