<?php

namespace Database\Factories;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStage>
 */
class ProjectStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'slug' => ProjectStageSlug::ABERTURA->value,
            'order' => 1,
            'responsible_sector' => ['fomentation', 'coord_fomentation'],
            'status' => ProjectStageStatus::PENDENTE->value,
            'responsible_user_id' => null,
            'started_at' => null,
            'concluded_at' => null,
            'deadline_at' => null,
            'rejection_reason' => null,
            'notes' => null,
        ];
    }

    public function emAndamento(): static
    {
        return $this->state([
            'status' => ProjectStageStatus::EM_ANDAMENTO->value,
            'started_at' => now(),
        ]);
    }

    public function aprovado(): static
    {
        return $this->state([
            'status' => ProjectStageStatus::APROVADO->value,
            'started_at' => now()->subDay(),
            'concluded_at' => now(),
        ]);
    }
}
