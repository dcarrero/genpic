<?php

namespace Database\Factories;

use App\Models\ArtisticStyle;
use App\Models\Generation;
use App\Models\GenerationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'generation_session_id' => GenerationSession::factory(),
            'artistic_style_id'     => ArtisticStyle::factory(),
            'status'                => 'completed',
            'provider'              => 'fal_ai',
            'provider_model'        => 'fal-ai/flux/dev',
            'raw_image_path'        => 'generations/test/' . fake()->uuid() . '.png',
            'raw_image_size_bytes'  => 2048000,
            'prompt_used'           => 'A renaissance portrait',
            'style_strength_used'   => 0.75,
            'queued_at'             => now()->subMinutes(2),
            'processing_started_at' => now()->subMinute(),
            'completed_at'          => now(),
            'elapsed_ms'            => 15000,
            'attempt_number'        => 1,
        ];
    }

    public function watermarked(): static
    {
        return $this->state([
            'status'                 => 'watermarked',
            'watermarked_image_path' => 'watermarked/test/' . fake()->uuid() . '.jpg',
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'        => 'failed',
            'error_message' => 'Provider timeout',
        ]);
    }
}
