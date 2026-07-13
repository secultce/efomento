<?php

namespace Tests\Feature;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\OpeningSupervisor;
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
        $this->service = app(ProjectStageService::class);
    }

    private function createUserWithRoles(string ...$roles): User
    {
        $user = User::factory()->create();
        foreach ($roles as $role) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        }

        return $user;
    }

    private function makePrincipalSupervisor(Project $project, User $user): void
    {
        $opening = $project->opening ?? Opening::factory()->create(['project_id' => $project->id]);

        OpeningSupervisor::create([
            'opening_id' => $opening->id,
            'user_id' => $user->id,
            'type' => 'principal',
            'is_active' => true,
            'assigned_at' => now(),
        ]);
    }

    public function test_observer_creates_7_stages_on_project_creation(): void
    {
        $project = Project::factory()->create();

        $this->assertCount(7, $project->stages);
        $this->assertDatabaseCount('project_stages', 7);
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
            ProjectStageSlug::PRESTACAO_DE_CONTAS,
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
            2 => $this->createUserWithRoles('legal_analysis'),
            3 => $this->createUserWithRoles('legal_analysis'),
            4 => $this->createUserWithRoles('budgetary'),
            5 => $this->createUserWithRoles('coord_financial'),
            6 => $this->createUserWithRoles('monitoring'),
            7 => $this->createUserWithRoles('monitoring'),
        ];

        $this->makePrincipalSupervisor($project, $userByOrder[6]);

        foreach (range(1, 7) as $order) {
            $stage = $project->stages()->where('order', $order)->first();
            $this->service->advance($stage, $userByOrder[$order]);
        }

        $project->refresh();

        $approvedCount = $project->stages()
            ->where('status', ProjectStageStatus::APROVADO)
            ->count();

        $this->assertEquals(7, $approvedCount);
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
        $user = $this->createUserWithRoles('legal_analysis');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser aprovada.');

        $this->service->advance($second, $user);
    }

    public function test_service_reject_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();
        $second = $project->stages()->where('order', 2)->first();
        $user = $this->createUserWithRoles('legal_analysis');

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
        $this->service->reject($second, 'Análise jurídica reprovada', $this->createUserWithRoles('legal_analysis'));

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
        $this->assertEquals(14, $project->getProgressPercentage());
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

        $this->assertDatabaseCount('project_stages', 7);

        $project->forceDelete();

        $this->assertDatabaseCount('project_stages', 0);
    }

    public function test_project_stages_relationship_returns_ordered(): void
    {
        $project = Project::factory()->create();

        $orders = $project->stages->pluck('order')->all();

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $orders);
    }

    // — requestNextInstallment —

    private function activateStage(Project $project, ProjectStageSlug $slug): ProjectStage
    {
        $stage = $project->stages()->where('slug', $slug->value)->firstOrFail();

        $project->stages()
            ->where('order', '<', $stage->order)
            ->update([
                'status' => ProjectStageStatus::APROVADO->value,
                'started_at' => now()->subDay(),
                'concluded_at' => now(),
            ]);

        $stage->update([
            'status' => ProjectStageStatus::EM_ANDAMENTO->value,
            'started_at' => now(),
        ]);

        return $stage->fresh();
    }

    public function test_request_next_installment_throws_when_user_is_not_principal_supervisor(): void
    {
        $project = Project::factory()
            ->for(Notice::factory()->state(['installments' => 2]))
            ->create(['current_installment_cycle' => 1]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Apenas o fiscal titular pode executar esta ação.');

        $this->service->requestNextInstallment($project, User::factory()->create());
    }

    public function test_request_next_installment_throws_when_notice_has_single_installment(): void
    {
        $project = Project::factory()
            ->for(Notice::factory()->state(['installments' => 1]))
            ->create();
        $user = $this->createUserWithRoles('monitoring');
        $this->makePrincipalSupervisor($project, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Projeto não possui múltiplas parcelas.');

        $this->service->requestNextInstallment($project, $user);
    }

    public function test_request_next_installment_throws_when_all_cycles_completed(): void
    {
        $project = Project::factory()
            ->for(Notice::factory()->state(['installments' => 2]))
            ->create(['current_installment_cycle' => 2]);
        $user = $this->createUserWithRoles('monitoring');
        $this->makePrincipalSupervisor($project, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Todos os ciclos de parcelas já foram concluídos.');

        $this->service->requestNextInstallment($project, $user);
    }

    public function test_request_next_installment_throws_when_monitoring_not_em_andamento(): void
    {
        $project = Project::factory()
            ->for(Notice::factory()->state(['installments' => 2]))
            ->create(['current_installment_cycle' => 1]);
        $user = $this->createUserWithRoles('monitoring');
        $this->makePrincipalSupervisor($project, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A etapa de Monitoramento precisa estar em andamento.');

        $this->service->requestNextInstallment($project, $user);
    }

    public function test_request_next_installment_increments_cycle_and_resets_stages(): void
    {
        $project = Project::factory()
            ->for(Notice::factory()->state(['installments' => 2]))
            ->create(['current_installment_cycle' => 1]);

        $this->activateStage($project, ProjectStageSlug::MONITORAMENTO);

        $user = $this->createUserWithRoles('monitoring');
        $this->makePrincipalSupervisor($project, $user);

        $this->service->requestNextInstallment($project, $user);

        $project->refresh();

        $this->assertEquals(2, $project->current_installment_cycle);

        $budget = $project->stages()->where('slug', ProjectStageSlug::ORCAMENTO)->first();
        $this->assertEquals(ProjectStageStatus::EM_ANDAMENTO, $budget->status);

        $payment = $project->stages()->where('slug', ProjectStageSlug::PAGAMENTO)->first();
        $this->assertEquals(ProjectStageStatus::BLOQUEADO, $payment->status);

        $monitoring = $project->stages()->where('slug', ProjectStageSlug::MONITORAMENTO)->first();
        $this->assertEquals(ProjectStageStatus::APROVADO, $monitoring->status);
    }
}
