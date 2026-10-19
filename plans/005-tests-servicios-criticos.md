# Plan 005: Tests de caracterización para los servicios de dinero y moderación (CouponService, OrderFulfillmentService, webhook Stripe firmado)

## Status

- **Priority**: P2
- **Effort**: M–L
- **Risk**: LOW (solo se añaden tests; no se modifica código de producción)
- **Depends on**: plans/002-baseline-tests-y-ci.md
- **Category**: tests
- **Planned at**: commit `61c6966`, 2026-06-11

## Why this matters

Los tres componentes por los que pasa el 100% del dinero del negocio tienen cobertura nula o trivial (verificado en el audit):

- `CouponService` (220 líneas): validación de cupones (caducidad, agotamiento, restricción por producto, un-uso-por-email) y aplicación de descuentos. Cero tests.
- `OrderFulfillmentService::confirmPayment()`: la máquina de estados del pago — idempotencia con lock, reconciliación de importes reales de Stripe, incremento de cupones, dispatch de jobs post-pago. Cero tests directos.
- `StripeWebhookController`: el test existente (`tests/Feature/StripeWebhookTest.php`) se salta la verificación de firma ("bypass firma para unit test") y no cubre `checkout.session.async_payment_succeeded` (SEPA/Bancontact) ni `payment_status != 'paid'`.

Una regresión aquí corrompe pedidos o cobra mal, y hoy nada la detectaría. Estos son tests de **caracterización**: fijan el comportamiento actual (que está en producción funcionando) para proteger refactors futuros — si un test revela un comportamiento que parece un bug, se reporta, no se "arregla" el código.

## Current state

### CouponService — `version-es/app/Services/CouponService.php`

- `validate(string $code, string $email, string $productKey): array` (líneas 75–111). Orden de comprobaciones (cada una corta y devuelve `['valid' => false, 'error' => '<clave>']`): `coupon_not_found` → `coupon_inactive` → `coupon_expired` (`$coupon->isExpired()`) → `coupon_exhausted` (`$coupon->isExhausted()`) → `coupon_wrong_product` (`$coupon->appliesToProduct($productKey)`) → `coupon_already_used` (solo si `once_per_email`; busca orders `paid/ready/processing` del email con ese cupón). Éxito: `['valid' => true, 'coupon' => $coupon]`.
- `apply(Order $order, Coupon $coupon): void` (líneas 116–127): calcula `discountCents($order->subtotal_cents)`, actualiza `coupon_code`, `discount_cents`, `total_cents = max(0, subtotal - descuento)` e incrementa `times_used`.
- `create()`/`createAbandonmentCoupon()`/`createInStripe()` llaman a la API de Stripe — pero `createInStripe()` (línea 151–156) hace short-circuit si `config('services.stripe.secret')` es null: devuelve ids null y solo crea el cupón local. En testing no hay secret ⇒ no hay llamadas de red. Verificarlo así en los tests (no mockear HTTP).
- Los métodos de dominio (`isExpired`, `isExhausted`, `appliesToProduct`, `discountCents`) viven en `version-es/app/Models/Coupon.php` — leerlos antes de escribir aserciones.

### OrderFulfillmentService — `version-es/app/Services/OrderFulfillmentService.php`

- `confirmPayment(Order $order, ?string $pi, ?string $customer, string $source, $checkoutSession = null): bool` (líneas 25–109):
  - Transacción con `lockForUpdate()` + re-fetch (línea 34).
  - Idempotencia: si `status` ∈ `['paid','processing','ready']` → log + `return false` (líneas 37–44).
  - Si se pasa `$checkoutSession` (objeto Stripe), reconcilia: `stripe_amount_total`→`total_cents`, `total_details.amount_discount`→`discount_cents`, `amount_tax`, `promotion_code` (líneas 57–80).
  - Marca `status='paid'`, `paid_at`, actualiza la sesión a `paid` (líneas 82–83).
  - Incrementa `times_used` del cupón con guard `whereNull('max_uses')->orWhereColumn('times_used','<','max_uses')` (líneas 86–90) — es decir, NO incrementa más allá de `max_uses`.
  - Fuera de la transacción: `dispatchPostPaymentJobs()` (batch de upsells o `GenerateFormatsJob`; para `web_basic`/`web_hd` sin upsells usa `dispatchSync`).
- Para `$checkoutSession` en tests basta un objeto plano con la misma forma (`(object)['amount_total' => 990, 'currency' => 'eur', 'total_details' => (object)[...]]`) — el servicio solo lee propiedades con `??`.

### StripeWebhookController — `version-es/app/Http/Controllers/StripeWebhookController.php`

- `handle()` (líneas 12–33): `\Stripe\Webhook::constructEvent($payload, $sigHeader, $secret)` con `config('services.stripe.webhook_secret')`; firma inválida → 400. Procesa `checkout.session.completed` y `checkout.session.async_payment_succeeded`.
- `handlePaymentConfirmed()` (líneas 35–60): para `completed` con `payment_status !== 'paid'` → log y return (espera el evento async). Busca la order por `stripe_checkout_id`; si no existe → warning y return (200 igualmente). Llama a `confirmPayment(...)`.
- **Cómo firmar payloads en tests** (esquema oficial de Stripe, no requiere red):
  ```php
  $payload = json_encode($event);                       // el JSON del evento
  $secret  = 'whsec_test_secret';                       // ponerlo en config en el test
  $ts      = time();
  $sig     = hash_hmac('sha256', "{$ts}.{$payload}", $secret);
  $header  = "t={$ts},v1={$sig}";
  $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'], $payload);
  ```
  En el test: `config(['services.stripe.webhook_secret' => $secret]);` antes de llamar. La ruta exacta del webhook está en `version-es/routes/web.php` (~línea 36, sin prefijo de locale ni CSRF) — confirmarla con `php artisan route:list | grep -i webhook`.

### Infra de tests existente

- phpunit.xml: sqlite `:memory:`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`. Patrón unit: `tests/Unit/Services/StorageServiceTest.php`. Patrón feature: `tests/Feature/StripeWebhookTest.php` y `tests/Feature/PaymentFlowTest.php` (usan factories de Order/Product/etc.).
- Factories existentes: ArtisticStyle, Format, Generation, GenerationSession, Order, OrderItem, Product, PurgeQueue, User. **No** existe `CouponFactory` — crearla (patrón: `OrderFactory`).
- Para no disparar jobs reales al confirmar pagos: `Bus::fake()` y `Queue::fake()` + `Mail::fake()` en los tests de `confirmPayment` (los jobs post-pago se despachan fuera de la transacción).

## Commands you will need

| Purpose | Command (desde `version-es/`) | Expected on success |
|---------|------------------------------|---------------------|
| Tests nuevos | `php artisan test --filter=CouponServiceTest` (etc.) | pasan |
| Suite | `php artisan test` | exit 0 |
| Lint | `php vendor/bin/pint --dirty` | exit 0 |

## Scope

**In scope** (crear):
- `version-es/tests/Unit/Services/CouponServiceTest.php`
- `version-es/tests/Unit/Services/OrderFulfillmentServiceTest.php`
- `version-es/tests/Feature/StripeWebhookSignedTest.php`
- `version-es/database/factories/CouponFactory.php`

**Out of scope** (NO tocar):
- CUALQUIER fichero de `version-es/app/` — este plan es solo de tests. Si un test revela un bug real, documentarlo en el informe y en `plans/README.md` (sección de hallazgos), no arreglarlo.
- `tests/Feature/StripeWebhookTest.php` existente — dejarlo; el nuevo fichero lo complementa.
- ContentModeration/IpBanService — cubierto por el plan 001.

## Git workflow

- Branch: `advisor/005-tests-servicios-criticos`
- Commits por servicio: `test: cobertura CouponService — validación y aplicación de cupones`, etc.
- No push salvo instrucción del operador.

## Steps

### Step 1: CouponFactory + CouponServiceTest

Crear `CouponFactory` (leer `app/Models/Coupon.php` y su migración para defaults sensatos: `active=true`, `type='percent'`, `value=20`, `once_per_email=true`, `max_uses=null`). Casos en `CouponServiceTest` (todos con `RefreshDatabase`, sin secret de Stripe configurado):

1. `validate()` con código inexistente → `['valid' => false, 'error' => 'coupon_not_found']`
2. cupón inactivo → `coupon_inactive`
3. caducado (`valid_until` en pasado) → `coupon_expired`
4. agotado (`max_uses=1`, `times_used=1`) → `coupon_exhausted`
5. producto no incluido en `product_keys` → `coupon_wrong_product`; con `product_keys=null` aplica a todo → válido
6. `once_per_email` con order previa `paid` del mismo email → `coupon_already_used`; con order `pending` → válido
7. `apply()`: descuento percent y fixed; `total_cents` nunca negativo (cupón mayor que subtotal → 0); `times_used` incrementado
8. `create()` sin Stripe secret → crea cupón local con `stripe_coupon_id` null (sin tocar red)

**Verify**: `php artisan test --filter=CouponServiceTest` → ≥8 tests pasan

### Step 2: OrderFulfillmentServiceTest

Con `RefreshDatabase` + `Bus::fake()` + `Queue::fake()` + `Mail::fake()`. Casos:

1. Order `pending` → `confirmPayment(...)` devuelve `true`; order queda `paid` con `paid_at`; la `GenerationSession` asociada queda `paid`.
2. Idempotencia: segunda llamada sobre la misma order → `false`, sin cambios adicionales.
3. Reconciliación: pasar `$checkoutSession = (object)['amount_total' => 1490, 'currency' => 'eur', 'total_details' => (object)['amount_discount' => 200, 'amount_tax' => 120], 'discounts' => []]` → `total_cents=1490`, `discount_cents=200`, `stripe_amount_tax=120` persistidos.
4. Cupón: order con `coupon_code` de un cupón `max_uses=1, times_used=0` → `times_used=1` tras confirmar; repetir con `times_used=1` (ya al límite) → NO pasa de 1.
5. Jobs: tras confirmar una order normal se despacha `GenerateFormatsJob` (asertar con `Bus::fake()`/`Queue::fake()` según cómo se despache — leer `dispatchPostPaymentJobs()` líneas 115–145; para orders con items upsell se usa `Bus::batch`, asertable con `Bus::fake()` y `Bus::assertBatched(...)`).

Nota: el caso 5 puede requerir Product/OrderItem reales — usar las factories existentes (`OrderFactory`, `OrderItemFactory`, `ProductFactory`) y mirar `tests/Feature/PaymentFlowTest.php` como referencia de montaje.

**Verify**: `php artisan test --filter=OrderFulfillmentServiceTest` → ≥5 tests pasan

### Step 3: StripeWebhookSignedTest

Feature test con payloads **firmados de verdad** (helper privado `signedPost(array $event)` usando el esquema del Current state). Casos:

1. Firma inválida (header corrupto) → 400, la order NO cambia.
2. `checkout.session.completed` con `payment_status='paid'` y `stripe_checkout_id` matching → 200, order pasa a `paid`.
3. `checkout.session.completed` con `payment_status='unpaid'` (pago async iniciado) → 200, order sigue `pending`.
4. `checkout.session.async_payment_succeeded` posterior → 200, order pasa a `paid`.
5. Webhook duplicado (mismo evento dos veces) → segundo retorna 200 y no re-procesa (order una sola vez `paid`, cupón no re-incrementado si aplica).
6. `stripe_checkout_id` desconocido → 200 (Stripe no debe reintentar) + ninguna order tocada.

Forma del payload mínimo del evento (objeto `checkout.session`): `['id' => 'evt_x', 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_test_x', 'payment_status' => 'paid', 'payment_intent' => 'pi_x', 'customer' => 'cus_x', 'amount_total' => 990, 'currency' => 'eur', 'total_details' => ['amount_discount' => 0, 'amount_tax' => 0]]]]` — ajustar si `\Stripe\Webhook::constructEvent` exige campos adicionales (`api_version`, `object: 'event'`, `created`): añadir lo mínimo hasta que parsee.

**Verify**: `php artisan test --filter=StripeWebhookSignedTest` → ≥6 tests pasan

### Step 4: Suite completa y recuento

**Verify**: `php artisan test` → exit 0, con ≥19 tests nuevos respecto a la baseline del plan 002.

## Test plan

Este plan ES el plan de tests. Patrones: `tests/Unit/Services/StorageServiceTest.php` (unit), `tests/Feature/PaymentFlowTest.php` (montaje con factories), `tests/Feature/StripeWebhookTest.php` (ruta del webhook).

## Done criteria

- [ ] `php artisan test --filter=CouponServiceTest` → exit 0 (≥8 tests)
- [ ] `php artisan test --filter=OrderFulfillmentServiceTest` → exit 0 (≥5 tests)
- [ ] `php artisan test --filter=StripeWebhookSignedTest` → exit 0 (≥6 tests)
- [ ] `php artisan test` → exit 0
- [ ] `git diff --stat -- version-es/app/` → vacío (ni una línea de producción tocada)
- [ ] Fila actualizada en `plans/README.md`; bugs descubiertos (si los hay) anotados ahí

## STOP conditions

- Un test exige tocar código de producción para poder pasar (p. ej. inyectar una dependencia) — reportar; no refactorizar producción en este plan.
- `\Stripe\Webhook::constructEvent` rechaza el payload por estructura (no por firma) tras 2 intentos de ajustar el JSON — reportar el error exacto en vez de desactivar la verificación de firma.
- Los tests revelan que la idempotencia o el guard de `max_uses` NO funcionan como describe el Current state — eso es un hallazgo de bug: parar ese caso, documentarlo y continuar con el resto.

## Maintenance notes

- Estos tests son la red de seguridad para: la subida de Laravel/Filament (diferida), el refactor de duplicación de jobs de generación (diferido), y cualquier cambio en precios/cupones.
- Al añadir un nuevo tipo de evento de webhook, añadir su caso firmado a `StripeWebhookSignedTest` — el patrón del helper `signedPost()` lo deja barato.
- Revisor: vigilar que futuros tests no vuelvan al patrón "bypass de firma" del test antiguo.
