<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Enums\SexualOrientation;
use App\Enums\RaceColor;
use App\Enums\DisabilityType;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\Education;
use App\Enums\Gender;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cpf' => fake()->unique()->numerify('###########'),

            'director_position' => fake()->jobTitle(),
            'director_email' => fake()->safeEmail(),

            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),

            'birth_date' => fake()->date(),
            
            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'postal_code' => fake()->postcode(),
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => 'CE',

            'gender' => fake()->randomElement(Gender::cases()),

            'education' => fake()->randomElement(Education::cases()),

            'sexual_orientation' => fake()->randomElement(
                SexualOrientation::cases()
            )->value,

            'race_or_color' => fake()->randomElement(
                RaceColor::cases()
            )->value,

            'has_disability' => fake()->randomElement(
                DisabilityType::cases()
            )->value,
        ];
    }
}