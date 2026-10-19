<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_is_auto_generated(): void
    {
        $order = Order::factory()->create();
        $this->assertNotNull($order->uuid);
        $this->assertEquals(36, strlen($order->uuid));
    }

    public function test_route_key_is_uuid(): void
    {
        $order = Order::factory()->create();
        $this->assertEquals('uuid', $order->getRouteKeyName());
    }

    public function test_is_paid_returns_true_for_paid_status(): void
    {
        $order = Order::factory()->paid()->create();
        $this->assertTrue($order->isPaid());
    }

    public function test_is_paid_returns_true_for_ready_status(): void
    {
        $order = Order::factory()->ready()->create();
        $this->assertTrue($order->isPaid());
    }

    public function test_is_paid_returns_false_for_pending(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $this->assertFalse($order->isPaid());
    }

    public function test_is_paid_returns_false_for_failed(): void
    {
        $order = Order::factory()->create(['status' => 'failed']);
        $this->assertFalse($order->isPaid());
    }

    public function test_has_items_relationship(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $this->assertCount(2, $order->items);
    }

    public function test_belongs_to_session(): void
    {
        $order = Order::factory()->create();
        $this->assertNotNull($order->session);
    }

    public function test_belongs_to_user(): void
    {
        $order = Order::factory()->create();
        $this->assertNotNull($order->user);
    }
}
