<?php

namespace Database\Factories;

use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $unit = 0;

        return [
            'duolingo_id' => fake()->unique()->uuid(),
            'title' => 'Section '.fake()->numberBetween(1, 5).', Unit '.($unit++ % 8 + 1),
            'section_number' => fake()->numberBetween(1, 5),
            'unit_number' => fake()->numberBetween(1, 8),
        ];
    }
}
