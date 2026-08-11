<?php

namespace Database\Factories;

use App\Models\BudgetAllocation;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetAllocation>
 */
class BudgetAllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notice_id' => Notice::factory(),
            'management_unit' => $this->faker->numerify('###/###'),
            'work_program' => $this->faker->numerify('#################'),
            'objective' => $this->faker->sentence(),
            'deliverable' => $this->faker->sentence(),
            'budget_function' => $this->faker->numerify('##'),
            'budget_subfunction' => $this->faker->numerify('###'),
            'project_activity' => $this->faker->numerify('####'),
            'expense_element' => $this->faker->numerify('######'),
            'funding_source' => $this->faker->numerify('##########'),
            'mapp' => $this->faker->numerify('####'),
            'finalistic_project' => $this->faker->sentence(3),
            'region_code' => $this->faker->numerify('##'),
            'planning_macroregion' => $this->faker->city(),
            'allocation_code' => $this->faker->numerify('######'),
            'allocation_number' => $this->faker->numerify('#########################################'),
            'created_by' => User::factory(),
        ];
    }
}
