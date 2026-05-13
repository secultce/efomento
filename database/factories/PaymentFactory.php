<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

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
