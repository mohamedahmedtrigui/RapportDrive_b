<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportEntry>
 */
class ReportEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'section' => fake()->randomElement(['client', 'chauffeur', 'service']),
            'course_id' => fake()->bothify('C-####'),
            'driver_id' => null,
            'description' => fake()->sentence(),
        ];
    }
}
