<?php

namespace Database\Factories;

use App\Models\LegalAnalysis;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalAnalysis>
 */
class LegalAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory(),
            'responsible_user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'processed_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
