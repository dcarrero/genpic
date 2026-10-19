<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email'      => fake()->unique()->safeEmail(),
            'locale'     => 'es',
            'ip_address' => fake()->ipv4(),
            'is_admin'   => false,
        ];
    }

    public function admin(): static
    {
        return $this->state([
            'is_admin' => true,
            'password' => bcrypt('admin'),
        ]);
    }
}
