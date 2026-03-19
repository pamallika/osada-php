<?php

namespace Database\Factories;

use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition()
    {
        return [
            'family_name' => $this->faker->lastName,
            'global_name' => $this->faker->firstName,
            'char_class' => 'Warrior',
            'gear_score' => 700,
            'attack' => 300,
            'awakening_attack' => 301,
            'defense' => 400,
        ];
    }
}
