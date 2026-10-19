<?php

namespace Tests\Unit\Models;

use App\Models\PurgeQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_pending_returns_due_entries(): void
    {
        PurgeQueue::factory()->create(['scheduled_at' => now()->subHour()]);
        PurgeQueue::factory()->future()->create();
        PurgeQueue::factory()->completed()->create();

        $pending = PurgeQueue::pending()->get();
        $this->assertCount(1, $pending);
    }

    public function test_paths_to_purge_is_cast_to_array(): void
    {
        $paths = ['originals/x/photo.jpg', 'generations/x/result.png'];
        $entry = PurgeQueue::factory()->create(['paths_to_purge' => $paths]);

        $this->assertIsArray($entry->paths_to_purge);
        $this->assertCount(2, $entry->paths_to_purge);
    }

    public function test_belongs_to_session(): void
    {
        $entry = PurgeQueue::factory()->create();
        $this->assertNotNull($entry->session);
    }
}
