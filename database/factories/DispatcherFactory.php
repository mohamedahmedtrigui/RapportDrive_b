<?php

namespace Database\Factories;

use App\Models\Dispatcher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispatcher>
 */
class DispatcherFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'ville_affectee' => fake()->city(),
            'password' => static::$password ??= 'password',
            'is_approved' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
