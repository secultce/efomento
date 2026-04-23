<?php

namespace Tests\Feature;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Services\ProjectStageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
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

    private function createUserWithRoles(string ...$roles): User
    {
        $user = User::factory()->create();
        foreach ($roles as $role) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        }

        return $user;
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

        $slugs = $project->stages()->pluck('slug')->all();

        $this->assertEquals([
            ProjectStageSlug::ABERTURA,
            ProjectStageSlug::ANALISE_JURIDICA,
            ProjectStageSlug::FORMALIZACAO,
            ProjectStageSlug::ORCAMENTO,
            ProjectStageSlug::PAGAMENTO,
            ProjectStageSlug::MONITORAMENTO,
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

        $statuses = $project->stages()
            ->where('order', '>', 1)
            ->pluck('status')
            ->all();

        foreach ($statuses as $status) {
            $this->assertEquals(ProjectStageStatus::PENDENTE, $status);
        }
    }

    public function test_full_happy_path_flow(): void
    {
        $project = Project::factory()->create();

        $userByOrder = [
            1 => $this->createUserWithRoles('fomentation'),
            2 => $this->createUserWithRoles('financial'),
            3 => $this->createUserWithRoles('legal_analysis'),
            4 => $this->createUserWithRoles('budgetary'),
            5 => $this->createUserWithRoles('coord_financial'),
            6 => $this->createUserWithRoles('monitoring'),
        ];

        foreach (range(1, 6) as $order) {
            $stage = $project->stages()->where('order', $order)->first();
            $this->service->advance($stage, $userByOrder[$order]);
        }

        $project->refresh();

        $approvedCount = $project->stages()
            ->where('status', ProjectStageStatus::APROVADO)
            ->count();

        $this->assertEquals(6, $approvedCount);
        $this->assertEquals(100, $project->getProgressPercentage());
    }

    public function test_advance_throws_when_user_lacks_role(): void
    {
        $project = Project::factory()->create();
        $first = $project->stages()->where('order', 1)->first();
        $user = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Você não tem permissão para tramitar esta etapa.');

        $this->service->advance($first, $user);
    }

    public function test_reject_throws_when_user_lacks_role(): void
    {
        $project = Project::factory()->create();
        $first = $project->stages()->where('order', 1)->first();
        $user = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Você não tem permissão para tramitar esta etapa.');

        $this->service->reject($first, 'Motivo', $user);
    }

    public function test_advance_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();
        $second = $project->stages()->where('order', 2)->first();
        $user = $this->createUserWithRoles('financial');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser aprovada.');

        $this->service->advance($second, $user);
    }

    public function test_service_reject_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();
        $second = $project->stages()->where('order', 2)->first();
        $user = $this->createUserWithRoles('financial');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser rejeitada.');

        $this->service->reject($second, 'Motivo', $user);
    }

    public function test_reject_flow_blocks_all_subsequent_stages(): void
    {
        $project = Project::factory()->create();

        $first = $project->stages()->where('order', 1)->first();
        $this->service->advance($first, $this->createUserWithRoles('fomentation'));

        $second = $project->stages()->where('order', 2)->first()->fresh();
        $this->service->reject($second, 'Análise jurídica reprovada', $this->createUserWithRoles('financial'));

        $statuses = $project->stages()
            ->where('order', '>', 2)
            ->pluck('status')
            ->all();

        foreach ($statuses as $status) {
            $this->assertEquals(ProjectStageStatus::BLOQUEADO, $status);
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
        $this->service->advance($first, $this->createUserWithRoles('fomentation'));

        $project->refresh();
        $this->assertEquals(17, $project->getProgressPercentage());
    }

    public function test_unique_constraint_prevents_duplicate_slug_per_project(): void
    {
        $project = Project::factory()->create();

        $this->expectException(QueryException::class);

        ProjectStage::create([
            'project_id' => $project->id,
            'slug' => ProjectStageSlug::ABERTURA,
            'order' => 99,
            'responsible_sector' => ['fomentation'],
            'status' => ProjectStageStatus::PENDENTE,
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
