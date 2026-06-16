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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = (clone $startDate)->modify('+1 year');

        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory(),
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),

            'effective_date_of_the_instrument' => $startDate,
            'expiration_date_of_the_instrument' => $endDate,

            'deadline_for_completing_report' => $this->faker->dateTimeBetween($startDate, '+6 months'),
            'deadline_for_analysis_and_issuance_of_the_opinion' => $this->faker->dateTimeBetween('+6 months', '+1 year'),

            'technical_opinions' => [
                ['suite_number' => $this->faker->numerify('####'), 'processing_date' => $this->faker->date()],
            ],
            'date_of_notification_to_the_agent' => $this->faker->dateTimeBetween('-3 months', 'now'),

            'observations' => $this->faker->optional()->paragraph(),

            'processed_at' => $this->faker->optional()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
