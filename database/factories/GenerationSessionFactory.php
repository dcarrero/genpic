<?php

namespace Database\Factories;

use App\Models\ArtisticStyle;
use App\Models\GenerationSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GenerationSession>
 */
class GenerationSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'style_key'           => 'renacimiento',
            'artistic_style_id'   => ArtisticStyle::factory(),
            'original_photo_path' => 'originals/test/' . fake()->uuid() . '.jpg',
            'original_filename'   => 'photo.jpg',
            'original_size_bytes' => 1024000,
            'face_detected'       => true,
            'status'              => 'pending',
            'ip_address'          => fake()->ipv4(),
            'locale'              => 'es',
        ];
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function watermarked(): static
    {
        return $this->state(['status' => 'watermarked']);
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    public function expired(): static
    {
        return $this->state([
            'status'     => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withoutUser(): static
    {
        return $this->state(['user_id' => null]);
    }
}
