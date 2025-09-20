<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemPrompt>
 */
class SystemPromptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'prompt' => $this->faker->paragraph(3),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
