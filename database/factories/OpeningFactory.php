<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\AgentStatus;
use App\Enums\OpeningStatus;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpeningFactory extends Factory
{
    protected $model = Opening::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'supervisor_id' => User::factory(),

            'opening_nup' => $this->faker->numerify('#####.######/####-##'),
            'opening_date' => $this->faker->date(),

            'agent_status' => $this->faker->randomElement(AgentStatus::cases())->value,
            'opened_by' => $this->faker->name(),

            'bank' => $this->faker->randomElement([
                '001',
                '237',
                '341',
                '104',
                '033',
                '745',
            ]),

            'account_type' => $this->faker->randomElement(AccountType::cases())->value,

            'branch' => $this->faker->numerify('####'),
            'account' => $this->faker->bankAccountNumber(),

            'is_draft' => $this->faker->boolean(),

            'status' => $this->faker->randomElement(OpeningStatus::cases())->value,

            'certificate_date' => $this->faker->optional()->dateTime(),

            'started_at' => now(),
            'submitted_at' => null,
            'concluded_at' => null,
        ];
    }
}
