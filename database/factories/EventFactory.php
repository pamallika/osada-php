<?php

namespace Database\Factories;

use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'start_at' => now()->addDays(1),
            'total_slots' => 0,
            'status' => 'draft',
        ];
    }
}
