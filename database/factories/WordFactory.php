<?php

namespace Database\Factories;

use App\Models\Word;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Word>
 */
class WordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' => fake()->unique()->lexify('???'),
            'pinyin' => fake()->lexify('???'),
            'translation' => fake()->word(),
            'tts_url' => null,
        ];
    }
}
