<?php

namespace Feature\Project;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectStageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Desativa o observer para criar stages manualmente nos testes unitários
        Project::flushEventListeners();
    }

    private function createStagesForProject(Project $project): void
    {
        $definitions = [
            [ProjectStageSlug::ABERTURA, 1, ['fomentation', 'coord_fomentation'], ProjectStageStatus::EM_ANDAMENTO],
            [ProjectStageSlug::ANALISE_JURIDICA, 2, ['financial', 'coord_financial', 'legal_analysis', 'coord_legal'], ProjectStageStatus::PENDENTE],
            [ProjectStageSlug::FORMALIZACAO, 3, ['legal_analysis', 'coord_legal'], ProjectStageStatus::PENDENTE],
            [ProjectStageSlug::ORCAMENTO, 4, ['budgetary', 'coord_budgetary'], ProjectStageStatus::PENDENTE],
            [ProjectStageSlug::PAGAMENTO, 5, ['financial', 'coord_financial'], ProjectStageStatus::PENDENTE],
            [ProjectStageSlug::MONITORAMENTO, 6, ['monitoring', 'coord_monitoring'], ProjectStageStatus::PENDENTE],
        ];

        foreach ($definitions as [$slug, $order, $roles, $status]) {
            ProjectStage::create([
                'project_id' => $project->id,
                'slug' => $slug->value,
                'order' => $order,
                'responsible_sector' => $roles,
                'status' => $status->value,
                'started_at' => $status === ProjectStageStatus::EM_ANDAMENTO ? now() : null,
            ]);
        }
    }

    public function test_fillable_fields(): void
    {
        $stage = new ProjectStage;

        $this->assertEquals([
            'project_id',
            'slug',
            'order',
            'responsible_sector',
            'status',
            'responsible_user_id',
            'started_at',
            'concluded_at',
            'deadline_at',
            'rejection_reason',
            'notes',
        ], $stage->getFillable());
    }

    public function test_enums_are_cast_correctly(): void
    {
        $project = Project::factory()->create();

        $stage = ProjectStage::create([
            'project_id' => $project->id,
            'slug' => ProjectStageSlug::ABERTURA->value,
            'order' => 1,
            'responsible_sector' => ['fomentation', 'coord_fomentation'],
            'status' => ProjectStageStatus::EM_ANDAMENTO->value,
        ]);

        $this->assertInstanceOf(ProjectStageSlug::class, $stage->slug);
        $this->assertIsArray($stage->responsible_sector);
        $this->assertContains('fomentation', $stage->responsible_sector);
        $this->assertContains('coord_fomentation', $stage->responsible_sector);
        $this->assertInstanceOf(ProjectStageStatus::class, $stage->status);
        $this->assertEquals(ProjectStageSlug::ABERTURA, $stage->slug);
        $this->assertEquals(ProjectStageStatus::EM_ANDAMENTO, $stage->status);
    }

    public function test_is_active_returns_true_when_em_andamento(): void
    {
        $project = Project::factory()->create();

        $stage = ProjectStage::create([
            'project_id' => $project->id,
            'slug' => ProjectStageSlug::ABERTURA->value,
            'order' => 1,
            'responsible_sector' => ['fomentation', 'coord_fomentation'],
            'status' => ProjectStageStatus::EM_ANDAMENTO->value,
        ]);

        $this->assertTrue($stage->isActive());
    }

    public function test_is_active_returns_false_when_not_em_andamento(): void
    {
        $project = Project::factory()->create();

        $stage = ProjectStage::create([
            'project_id' => $project->id,
            'slug' => ProjectStageSlug::ABERTURA->value,
            'order' => 1,
            'responsible_sector' => ['fomentation', 'coord_fomentation'],
            'status' => ProjectStageStatus::PENDENTE->value,
        ]);

        $this->assertFalse($stage->isActive());
    }

    public function test_mark_approved_changes_status_and_sets_concluded_at(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $stage = $project->stages()->where('order', 1)->first();

        $stage->markApproved();
        $stage->refresh();

        $this->assertEquals(ProjectStageStatus::APROVADO, $stage->status);
        $this->assertNotNull($stage->concluded_at);
    }

    public function test_mark_approved_last_stage_does_not_error(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $project->stages()->where('order', '<', 6)->update([
            'status' => ProjectStageStatus::APROVADO->value,
            'concluded_at' => now(),
        ]);

        $last = $project->stages()->where('order', 6)->first();
        $last->update(['status' => ProjectStageStatus::EM_ANDAMENTO->value]);

        $last->markApproved();
        $last->refresh();

        $this->assertEquals(ProjectStageStatus::APROVADO, $last->status);
        $this->assertNull($last->getNextStage());
    }

    public function test_mark_rejected_changes_status_and_sets_reason(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $stage = $project->stages()->where('order', 1)->first();

        $stage->markRejected('Documentação incompleta');
        $stage->refresh();

        $this->assertEquals(ProjectStageStatus::REJEITADO, $stage->status);
        $this->assertEquals('Documentação incompleta', $stage->rejection_reason);
        $this->assertNotNull($stage->concluded_at);
    }

    public function test_can_advance_first_stage_always_true(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $first = $project->stages()->where('order', 1)->first();

        $this->assertTrue($first->canAdvance());
    }

    public function test_can_advance_returns_true_when_all_previous_approved(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $project->stages()->where('order', 1)->update([
            'status' => ProjectStageStatus::APROVADO->value,
        ]);

        $second = $project->stages()->where('order', 2)->first();
        $this->assertTrue($second->canAdvance());
    }

    public function test_can_advance_returns_false_when_previous_not_approved(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $second = $project->stages()->where('order', 2)->first();

        $this->assertFalse($second->canAdvance());
    }

    public function test_get_next_stage(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $first = $project->stages()->where('order', 1)->first();
        $next = $first->getNextStage();

        $this->assertNotNull($next);
        $this->assertEquals(2, $next->order);
        $this->assertEquals(ProjectStageSlug::ANALISE_JURIDICA, $next->slug);
    }

    public function test_get_next_stage_returns_null_for_last(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $last = $project->stages()->where('order', 6)->first();

        $this->assertNull($last->getNextStage());
    }

    public function test_get_previous_stage(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $second = $project->stages()->where('order', 2)->first();
        $prev = $second->getPreviousStage();

        $this->assertNotNull($prev);
        $this->assertEquals(1, $prev->order);
        $this->assertEquals(ProjectStageSlug::ABERTURA, $prev->slug);
    }

    public function test_get_previous_stage_returns_null_for_first(): void
    {
        $project = Project::factory()->create();
        $this->createStagesForProject($project);

        $first = $project->stages()->where('order', 1)->first();

        $this->assertNull($first->getPreviousStage());
    }

    public function test_get_progress_percentage(): void
    {
        $project = new Project;
        $this->assertIsInt($project->getProgressPercentage());
    }
}
