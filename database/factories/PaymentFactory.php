<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Project;
use App\Models\Payment;

class PaymentFactory extends Factory
{

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),

            'creditor_requested_at' => $this->faker->date(),
            'creditor_registration_number' => $this->faker->numerify('########'),
            'communication_sent_at' => $this->faker->date(),
            'contact_notes' => $this->faker->sentence(),
        ];
    }
}