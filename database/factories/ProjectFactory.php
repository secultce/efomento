<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Notice;
use App\Models\Project;
use App\Enums\Education;
use App\Enums\Gender;
use App\Enums\SexualOrientation;
use App\Enums\RaceColor;
use App\Enums\DisabilityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => $this->faker->unique()->bothify('REG-####-????'),
            'number' => $this->faker->bothify('PROJ-####'),
            'opportunity_id' => $this->faker->numberBetween(1, 100),
            'category_id' => $this->faker->numberBetween(1, 10),
            'agent_id' => Agent::factory(),
            'notice_id' => Notice::factory(),
            'education_level' => $this->faker->randomElement(Education::cases()),
            'gender' => $this->faker->randomElement(Gender::cases()),
            'sexual_orientation' => $this->faker->randomElement(SexualOrientation::cases()),
            'race' => $this->faker->randomElement(RaceColor::cases()),
            'has_disability' => $this->faker->randomElement(DisabilityType::cases()),
            'create_timestamp' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'sent_timestamp' => $this->faker->dateTimeBetween('now', '+1 month'),
            'consolidated_result' => $this->faker->sentence(),
            'data_registration' => [
                'title' => $this->faker->sentence(),
                'description' => $this->faker->paragraph(),
            ],
        ];
    }
}
