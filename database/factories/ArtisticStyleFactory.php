<?php

namespace Database\Factories;

use App\Models\ArtisticStyle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtisticStyle>
 */
class ArtisticStyleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'style_key'      => fake()->unique()->slug(2),
            'label'          => ['es' => 'Estilo ' . fake()->word(), 'en' => 'Style ' . fake()->word()],
            'description'    => ['es' => fake()->sentence(), 'en' => fake()->sentence()],
            'label_es'       => 'Estilo ' . fake()->word(),
            'label_en'       => 'Style ' . fake()->word(),
            'description_es' => fake()->sentence(),
            'description_en' => fake()->sentence(),
            'base_prompt'    => 'A portrait in the style of ' . fake()->word(),
            'negative_prompt'=> 'blurry, distorted',
            'style_strength' => 0.75,
            'phase'          => 1,
            'sort_order'     => 0,
            'active'         => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
