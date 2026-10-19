<?php

namespace Database\Factories;

use App\Models\GenerationSession;
use App\Models\PurgeQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurgeQueue>
 */
class PurgeQueueFactory extends Factory
{
    protected $model = PurgeQueue::class;

    public function definition(): array
    {
        $uuid = fake()->uuid();

        return [
            'generation_session_id' => GenerationSession::factory(),
            'paths_to_purge'        => [
                "originals/{$uuid}/photo.jpg",
                "generations/{$uuid}/result.png",
                "watermarked/{$uuid}/preview.jpg",
            ],
            'estimated_bytes' => 5000000,
            'status'          => 'pending',
            'scheduled_at'    => now()->subHour(),
        ];
    }

    public function future(): static
    {
        return $this->state(['scheduled_at' => now()->addDay()]);
    }

    public function completed(): static
    {
        return $this->state([
            'status'       => 'completed',
            'processed_at' => now(),
            'bytes_freed'  => 5000000,
        ]);
    }
}
