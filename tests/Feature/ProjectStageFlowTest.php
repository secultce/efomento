<?php

namespace Tests\Feature;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\ProjectStageService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProjectStageFlowTest extends TestCase
{
    use RefreshDatabase;

    private ProjectStageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectStageService;
    }

    public function test_observer_creates_6_stages_on_project_creation(): void
    {
        $project = Project::factory()->create();

        $this->assertCount(6, $project->stages);
        $this->assertDatabaseCount('project_stages', 6);
    }

    public function test_stages_are_created_in_correct_order(): void
    {
        $project = Project::factory()->create();

        $slugs = $project->stages()->pluck('slug')->map(fn ($s) => $s->value)->all();

        $this->assertEquals([
            ProjectStageSlug::ABERTURA->value,
            ProjectStageSlug::ANALISE_JURIDICA->value,
            ProjectStageSlug::FORMALIZACAO->value,
            ProjectStageSlug::ORCAMENTO->value,
            ProjectStageSlug::PAGAMENTO->value,
            ProjectStageSlug::MONITORAMENTO->value,
        ], $slugs);
    }

    public function test_first_stage_starts_as_em_andamento(): void
    {
        $project = Project::factory()->create();

        $first = $project->stages()->where('order', 1)->first();

        $this->assertEquals(ProjectStageStatus::EM_ANDAMENTO, $first->status);
        $this->assertNotNull($first->started_at);
    }

    public function test_remaining_stages_start_as_pendente(): void
    {
        $project = Project::factory()->create();

        $pendentes = $project->stages()
            ->where('order', '>', 1)
            ->pluck('status')
            ->map(fn ($s) => $s->value)
            ->all();

        foreach ($pendentes as $status) {
            $this->assertEquals(ProjectStageStatus::PENDENTE->value, $status);
        }
    }

    public function test_full_happy_path_flow(): void
    {
        $project = Project::factory()->create();

        foreach (range(1, 6) as $order) {
            $stage = $project->stages()->where('order', $order)->first();
            $this->service->advance($stage);
        }

        $project->refresh();

        $approvedCount = $project->stages()
            ->where('status', ProjectStageStatus::APROVADO->value)
            ->count();

        $this->assertEquals(6, $approvedCount);
        $this->assertEquals(100, $project->getProgressPercentage());
    }

    public function test_advance_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();

        $second = $project->stages()->where('order', 2)->first();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser aprovada.');

        $this->service->advance($second);
    }

    public function test_service_reject_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();

        $second = $project->stages()->where('order', 2)->first();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser rejeitada.');

        $this->service->reject($second, 'Motivo');
    }

    public function test_reject_flow_blocks_all_subsequent_stages(): void
    {
        $project = Project::factory()->create();

        // Aprova a primeira etapa
        $first = $project->stages()->where('order', 1)->first();
        $this->service->advance($first);

        // Rejeita a segunda
        $second = $project->stages()->where('order', 2)->first()->fresh();
        $this->service->reject($second, 'Análise jurídica reprovada');

        $blockedStages = $project->stages()
            ->where('order', '>', 2)
            ->pluck('status')
            ->map(fn ($s) => $s->value)
            ->all();

        foreach ($blockedStages as $status) {
            $this->assertEquals(ProjectStageStatus::BLOQUEADO->value, $status);
        }
    }

    public function test_current_stage_relationship(): void
    {
        $project = Project::factory()->create();

        $current = $project->currentStage;

        $this->assertNotNull($current);
        $this->assertEquals(ProjectStageSlug::ABERTURA, $current->slug);
        $this->assertEquals(ProjectStageStatus::EM_ANDAMENTO, $current->status);
    }

    public function test_get_current_stage_name(): void
    {
        $project = Project::factory()->create();

        $this->assertEquals('Abertura', $project->getCurrentStageName());
    }

    public function test_progress_percentage_zero_at_start(): void
    {
        $project = Project::factory()->create();

        $this->assertEquals(0, $project->getProgressPercentage());
    }

    public function test_progress_percentage_updates_after_approvals(): void
    {
        $project = Project::factory()->create();

        $first = $project->stages()->where('order', 1)->first();
        $this->service->advance($first);

        $project->refresh();
        // 1 de 6 aprovadas ≈ 17%
        $this->assertEquals(17, $project->getProgressPercentage());
    }

    public function test_unique_constraint_prevents_duplicate_slug_per_project(): void
    {
        $project = Project::factory()->create();

        $this->expectException(QueryException::class);

        ProjectStage::create([
            'project_id' => $project->id,
            'slug' => ProjectStageSlug::ABERTURA->value,
            'order' => 99,
            'responsible_sector' => 'c_finalistica',
            'status' => ProjectStageStatus::PENDENTE->value,
        ]);
    }

    public function test_cascade_delete_removes_stages_with_project(): void
    {
        $project = Project::factory()->create();

        $this->assertDatabaseCount('project_stages', 6);

        $project->forceDelete();

        $this->assertDatabaseCount('project_stages', 0);
    }

    public function test_project_stages_relationship_returns_ordered(): void
    {
        $project = Project::factory()->create();

        $orders = $project->stages->pluck('order')->all();

        $this->assertEquals([1, 2, 3, 4, 5, 6], $orders);
    }
}
