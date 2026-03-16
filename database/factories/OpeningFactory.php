<?php

namespace Database\Factories;

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

            'opening_nup' => $this->faker->numerify('#####.######/####-##'),
            'opening_date' => $this->faker->date(),

            'agent_status' => $this->faker->randomElement([
                'ativo',
                'pendente',
                'inativo'
            ]),

            'opened_by' => $this->faker->name(),

            'bank' => $this->faker->randomElement([
                'Banco do Brasil',
                'Caixa',
                'Bradesco',
                'Santander'
            ]),

            'account_type' => $this->faker->randomElement([
                'corrente',
                'poupanca'
            ]),

            'branch' => $this->faker->numerify('####'),
            'account' => $this->faker->bankAccountNumber(),

            'is_draft' => $this->faker->boolean(),

            'status' => $this->faker->randomElement([
                'pendente',
                'em_andamento',
                'concluido',
                'rejeitado'
            ]),

            'started_at' => now(),
            'submitted_at' => null,
            'concluded_at' => null,
        ];
    }
}