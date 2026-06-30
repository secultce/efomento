<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NoticeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nup' => $this->faker->unique()->numerify('NUP-######'),
            'notice_url' => $this->faker->url(),
            'external_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'total_notice_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'total_commitment_amount' => $this->faker->randomFloat(2, 500, 30000),
            'installments' => $this->faker->numberBetween(1, 12),
            'process_manager' => $this->faker->name(),
            'process_manager_email' => $this->faker->safeEmail(),
            'creditor_registration_nup' => $this->faker->numerify('CR-#####'),
            'creditor_registration_request_date' => $this->faker->date(),
            'budget_allocation_nup' => $this->faker->unique()->numerify('BA-#####'),
            'budget_allocation_request_date' => $this->faker->date(),
        ];
    }
}
