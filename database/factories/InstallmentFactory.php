<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'request_date' => $this->faker->dateTimeBetween('-1 year', 'now'),

            'justification' => $this->faker->optional()->sentence(),
            'observations' => $this->faker->optional()->paragraph(),

            'installment_number' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }
}
