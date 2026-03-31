<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentProcessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creditor_requested_at' => $this->faker->date(),
            'creditor_status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'creditor_registration_number' => $this->faker->numerify('########'),
            'communication_sent_at' => $this->faker->date(),
            'contact_notes' => $this->faker->sentence(),
        ];
    }
}
