<?php

namespace Database\Factories;

use App\Models\Guild;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GuildFactory extends Factory
{
    protected $model = Guild::class;

    public function definition(): array
    {
        $name = $this->faker->company;
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'owner_id' => User::factory(),
            'logo_url' => $this->faker->imageUrl(),
        ];
    }
}
