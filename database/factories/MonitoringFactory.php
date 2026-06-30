<?php

namespace Database\Factories;

use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitoring>
 */
class MonitoringFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory(),
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'technical_opinions' => [
                ['suite_number' => $this->faker->numerify('####'), 'processing_date' => $this->faker->date()],
            ],
            'observations' => $this->faker->optional()->paragraph(),
            'data_registration' => null,
        ];
    }
}
