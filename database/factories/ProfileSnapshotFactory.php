<?php

namespace Database\Factories;

use App\Enums\DisabilityType;
use App\Enums\Education;
use App\Enums\ProfileSnapshotSource;
use App\Enums\RaceColor;
use App\Enums\SexualOrientation;
use App\Models\Agent;
use App\Models\ProfileSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileSnapshotFactory extends Factory
{
    protected $model = ProfileSnapshot::class;

    public function definition(): array
    {
        return [
            'object_id' => Agent::factory(),
            'object_type' => 'agent',
            'gender' => fake()->randomElement(['Homem Cis', 'Mulher Cis', 'Não-binário', 'Outro']),
            'sexual_orientation' => fake()->randomElement(SexualOrientation::cases())->value,
            'race' => fake()->randomElement(RaceColor::cases())->value,
            'education' => fake()->randomElement(Education::cases())->value,
            'has_disability' => fake()->randomElement(DisabilityType::cases())->value,
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
            'recorded_at' => now(),
            'source' => ProfileSnapshotSource::AGENT_UPDATE,
        ];
    }

    public function forProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'object_type' => 'project',
            'source' => ProfileSnapshotSource::PROJECT_REGISTRATION,
            'street' => null,
            'number' => null,
            'complement' => null,
            'postal_code' => null,
            'neighborhood' => null,
            'city' => null,
            'state' => null,
        ]);
    }
}
