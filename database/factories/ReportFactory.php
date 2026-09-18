<?php

namespace Database\Factories;

use App\Models\Dispatcher;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispatcher_id' => Dispatcher::factory(),
            'titre' => fake()->catchPhrase(),
            'date_rapport' => fake()->date(),
            'statut' => 'en_attente',
        ];
    }
}
