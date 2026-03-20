<?php

namespace Database\Factories;

use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpeningSupervisorFactory extends Factory
{
    protected $model = OpeningSupervisor::class;

    public function definition(): array
    {
        return [
            'opening_id'  => Opening::factory(),
            'user_id'     => User::factory(),
            'assigned_by' => User::factory(),
            'is_active'   => true,
            'assigned_at' => now(),
            'removed_at'  => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active'  => false,
            'removed_at' => now(),
        ]);
    }
}
