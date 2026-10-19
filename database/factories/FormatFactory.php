<?php

namespace Database\Factories;

use App\Models\Format;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Format>
 */
class FormatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'format_key'       => fake()->unique()->slug(2),
            'label'            => ['es' => 'Formato ' . fake()->word(), 'en' => 'Format ' . fake()->word()],
            'label_es'         => 'Formato ' . fake()->word(),
            'label_en'         => 'Format ' . fake()->word(),
            'max_dimension_px' => 2048,
            'file_type'        => 'jpg',
            'quality'          => 85,
            'active'           => true,
        ];
    }
}
