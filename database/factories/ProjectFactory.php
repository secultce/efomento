<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Notice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => $this->faker->unique()->bothify('REG-####-????'),
            'number' => $this->faker->bothify('PROJ-####'),
            'category_id' => Category::factory(),
            'agent_id' => Agent::factory(),
            'notice_id' => Notice::factory(),
        ];
    }
}
