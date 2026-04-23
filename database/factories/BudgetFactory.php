<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
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
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),

            'processing_date_for_codip' => $this->faker->optional()->date(),
            'processing_date_for_coafi' => $this->faker->optional()->date(),
            'date_of_expense_breakdown_table' => $this->faker->optional()->date(),

            'processed_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
