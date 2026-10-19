<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Sin Stripe secret: createInStripe() hará short-circuit y devolverá ids null
        config(['services.stripe.secret' => null]);
        $this->service = new CouponService();
    }

    // ─── validate(): casos de error ───────────────────────────────────────────

    public function test_validate_codigo_inexistente_devuelve_coupon_not_found(): void
    {
        $result = $this->service->validate('NO-EXISTE', 'user@example.com', 'web_basic');

        $this->assertFalse($result['valid']);
        $this->assertSame('coupon_not_found', $result['error']);
    }

    public function test_validate_cupon_inactivo_devuelve_coupon_inactive(): void
    {
        $coupon = Coupon::factory()->inactive()->create(['code' => 'INACTIVO']);

        $result = $this->service->validate('INACTIVO', 'user@example.com', 'web_basic');

        $this->assertFalse($result['valid']);
        $this->assertSame('coupon_inactive', $result['error']);
    }

    public function test_validate_cupon_caducado_devuelve_coupon_expired(): void
    {
        $coupon = Coupon::factory()->expired()->create(['code' => 'CADUCADO']);

        $result = $this->service->validate('CADUCADO', 'user@example.com', 'web_basic');

        $this->assertFalse($result['valid']);
        $this->assertSame('coupon_expired', $result['error']);
    }

    public function test_validate_cupon_agotado_devuelve_coupon_exhausted(): void
    {
        $coupon = Coupon::factory()->exhausted()->create(['code' => 'AGOTADO']);

        $result = $this->service->validate('AGOTADO', 'user@example.com', 'web_basic');

        $this->assertFalse($result['valid']);
        $this->assertSame('coupon_exhausted', $result['error']);
    }

    public function test_validate_cupon_producto_incorrecto_devuelve_coupon_wrong_product(): void
    {
        $coupon = Coupon::factory()->forProduct('print_a4')->create(['code' => 'SOLO_PRINT']);

        $result = $this->service->validate('SOLO_PRINT', 'user@example.com', 'web_basic');

        $this->assertFalse($result['valid']);
        $this->assertSame('coupon_wrong_product', $result['error']);
    }

    public function test_validate_cupon_con_product_keys_null_aplica_a_todos_los_productos(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'UNIVERSAL', 'product_keys' => null]);

        $result = $this->service->validate('UNIVERSAL', 'user@example.com', 'web_basic');

        $this->assertTrue($result['valid']);
        $this->assertSame($coupon->id, $result['coupon']->id);
    }

    public function test_validate_once_per_email_con_order_paid_previa_devuelve_coupon_already_used(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'ONCE', 'once_per_email' => true]);
        $user   = User::factory()->create(['email' => 'comprador@example.com']);
        Order::factory()->paid()->create([
            'user_id'     => $user->id,
            'coupon_code' => $coupon->code,
        ]);

        $result = $this->service->validate('ONCE', 'comprador@example.com', 'web_basic');

        $this->assertFalse($result['valid']);
        $this->assertSame('coupon_already_used', $result['error']);
    }

    public function test_validate_once_per_email_con_order_pending_previa_es_valido(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'ONCE2', 'once_per_email' => true]);
        $user   = User::factory()->create(['email' => 'comprador2@example.com']);
        Order::factory()->create([
            'user_id'     => $user->id,
            'coupon_code' => $coupon->code,
            'status'      => 'pending',
        ]);

        $result = $this->service->validate('ONCE2', 'comprador2@example.com', 'web_basic');

        $this->assertTrue($result['valid']);
    }

    // ─── apply(): percent y fixed ─────────────────────────────────────────────

    public function test_apply_descuento_percent_actualiza_order_correctamente(): void
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'PCT20',
            'type'  => 'percent',
            'value' => 20,
        ]);
        $order = Order::factory()->create(['subtotal_cents' => 1000, 'total_cents' => 1000]);

        $this->service->apply($order, $coupon);

        $order->refresh();
        $this->assertSame('PCT20', $order->coupon_code);
        $this->assertSame(200, $order->discount_cents);   // 20% de 1000
        $this->assertSame(800, $order->total_cents);
    }

    public function test_apply_descuento_fixed_actualiza_order_correctamente(): void
    {
        $coupon = Coupon::factory()->fixed(300)->create(['code' => 'FIXED300']);
        $order  = Order::factory()->create(['subtotal_cents' => 999, 'total_cents' => 999]);

        $this->service->apply($order, $coupon);

        $order->refresh();
        $this->assertSame(300, $order->discount_cents);
        $this->assertSame(699, $order->total_cents);
    }

    public function test_apply_total_cents_nunca_es_negativo(): void
    {
        // Cupón de tipo fixed con valor mayor que el subtotal
        $coupon = Coupon::factory()->fixed(9999)->create(['code' => 'GRATIS']);
        $order  = Order::factory()->create(['subtotal_cents' => 499, 'total_cents' => 499]);

        $this->service->apply($order, $coupon);

        $order->refresh();
        $this->assertSame(0, $order->total_cents);
    }

    public function test_apply_incrementa_times_used_del_cupon(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'INCR', 'times_used' => 0]);
        $order  = Order::factory()->create(['subtotal_cents' => 500, 'total_cents' => 500]);

        $this->service->apply($order, $coupon);

        $this->assertSame(1, $coupon->fresh()->times_used);
    }

    // ─── create() sin Stripe ──────────────────────────────────────────────────

    public function test_create_sin_stripe_secret_crea_cupon_local_con_ids_null(): void
    {
        $coupon = $this->service->create('ADMINTEST', 'percent', 15, null, 50, true);

        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertSame('ADMINTEST', $coupon->code);
        $this->assertNull($coupon->stripe_coupon_id);
        $this->assertNull($coupon->stripe_promotion_code_id);
        $this->assertSame(50, $coupon->max_uses);
        $this->assertTrue($coupon->active);
    }
}
