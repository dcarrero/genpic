<?php

namespace Tests\Unit\Models;

use App\Models\GenerationSession;
use App\Models\Generation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_is_auto_generated(): void
    {
        $session = GenerationSession::factory()->create();
        $this->assertNotNull($session->uuid);
        $this->assertTrue(strlen($session->uuid) === 36);
    }

    public function test_expires_at_is_auto_set_from_config(): void
    {
        $session = GenerationSession::factory()->create();
        $this->assertNotNull($session->expires_at);
        $this->assertTrue($session->expires_at->isFuture());
    }

    public function test_is_expired_returns_true_for_past_date(): void
    {
        $session = GenerationSession::factory()->expired()->create();
        $this->assertTrue($session->isExpired());
    }

    public function test_is_expired_returns_false_for_future_date(): void
    {
        $session = GenerationSession::factory()->create();
        $this->assertFalse($session->isExpired());
    }

    public function test_is_paid_checks_status(): void
    {
        $paid = GenerationSession::factory()->paid()->create();
        $pending = GenerationSession::factory()->create();

        $this->assertTrue($paid->isPaid());
        $this->assertFalse($pending->isPaid());
    }

    public function test_route_key_is_uuid(): void
    {
        $session = GenerationSession::factory()->create();
        $this->assertEquals('uuid', $session->getRouteKeyName());
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $session = GenerationSession::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $session->user->id);
    }

    public function test_has_many_generations(): void
    {
        $session = GenerationSession::factory()->create();
        Generation::factory()->count(2)->create(['generation_session_id' => $session->id]);

        $this->assertCount(2, $session->generations);
    }

    public function test_has_many_orders(): void
    {
        $session = GenerationSession::factory()->create();
        Order::factory()->count(2)->create([
            'generation_session_id' => $session->id,
            'user_id'              => $session->user_id,
        ]);

        $this->assertCount(2, $session->orders);
    }

    public function test_latest_generation_returns_most_recent(): void
    {
        $session = GenerationSession::factory()->create();
        Generation::factory()->create([
            'generation_session_id' => $session->id,
            'created_at'           => now()->subHour(),
        ]);
        $latest = Generation::factory()->create([
            'generation_session_id' => $session->id,
            'created_at'           => now(),
        ]);

        $this->assertEquals($latest->id, $session->latestGeneration->id);
    }
}
