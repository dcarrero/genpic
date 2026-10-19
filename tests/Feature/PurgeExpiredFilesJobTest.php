<?php

namespace Tests\Feature;

use App\Jobs\PurgeExpiredFilesJob;
use App\Models\GenerationSession;
use App\Models\Order;
use App\Models\PurgeQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeExpiredFilesJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_purges_expired_files(): void
    {
        $session = GenerationSession::factory()->create();
        $paths = ['originals/test/photo.jpg', 'generations/test/result.png'];

        foreach ($paths as $path) {
            Storage::disk('local')->put($path, 'content');
        }

        PurgeQueue::factory()->create([
            'generation_session_id' => $session->id,
            'paths_to_purge'        => $paths,
            'scheduled_at'          => now()->subHour(),
        ]);

        (new PurgeExpiredFilesJob)->handle(app(\App\Services\StorageService::class));

        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }

        $entry = PurgeQueue::first();
        $this->assertEquals('completed', $entry->status);
        $this->assertNotNull($entry->processed_at);
        $this->assertGreaterThan(0, $entry->bytes_freed);

        $session->refresh();
        $this->assertEquals('expired', $session->status);
    }

    public function test_skips_sessions_with_paid_orders(): void
    {
        $session = GenerationSession::factory()->create();
        Order::factory()->paid()->create([
            'generation_session_id' => $session->id,
            'user_id'              => $session->user_id,
        ]);

        $path = 'originals/paid/photo.jpg';
        Storage::disk('local')->put($path, 'content');

        PurgeQueue::factory()->create([
            'generation_session_id' => $session->id,
            'paths_to_purge'        => [$path],
            'scheduled_at'          => now()->subHour(),
        ]);

        (new PurgeExpiredFilesJob)->handle(app(\App\Services\StorageService::class));

        // El archivo NO debe borrarse
        Storage::disk('local')->assertExists($path);

        $entry = PurgeQueue::first();
        $this->assertEquals('completed', $entry->status);
    }

    public function test_handles_missing_files_gracefully(): void
    {
        $session = GenerationSession::factory()->create();

        PurgeQueue::factory()->create([
            'generation_session_id' => $session->id,
            'paths_to_purge'        => ['nonexistent/file.jpg'],
            'scheduled_at'          => now()->subHour(),
        ]);

        (new PurgeExpiredFilesJob)->handle(app(\App\Services\StorageService::class));

        $entry = PurgeQueue::first();
        $this->assertEquals('completed', $entry->status);
        $this->assertEquals(0, $entry->bytes_freed);
    }

    public function test_does_not_process_future_entries(): void
    {
        $session = GenerationSession::factory()->create();

        PurgeQueue::factory()->future()->create([
            'generation_session_id' => $session->id,
        ]);

        (new PurgeExpiredFilesJob)->handle(app(\App\Services\StorageService::class));

        $entry = PurgeQueue::first();
        $this->assertEquals('pending', $entry->status);
    }
}
