<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_attribute_extracts_local_part_of_email(): void
    {
        $user = User::factory()->create(['email' => 'john.doe@example.com']);
        $this->assertEquals('john.doe', $user->name);
    }

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->create(['email' => 'test-admin@genpic.com']);
        // config() es la forma correcta: ADMIN_EMAILS se evalúa en tiempo de boot,
        // modificar $_ENV después no tiene efecto sobre config('genpic.admin_emails').
        config(['genpic.admin_emails' => ['test-admin@genpic.com']]);
        $panel = app(Panel::class);

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    public function test_regular_user_cannot_access_filament_panel(): void
    {
        $user = User::factory()->create(['email' => 'regular@example.com']);
        config(['genpic.admin_emails' => ['admin-only@genpic.com']]);
        $panel = app(Panel::class);

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_user_has_generation_sessions_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertCount(0, $user->generationSessions);
    }

    public function test_user_has_orders_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertCount(0, $user->orders);
    }
}
