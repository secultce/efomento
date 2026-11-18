<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'director_position' => fake()->jobTitle(),
            'director_email' => fake()->safeEmail(),
        ];
    }
}
