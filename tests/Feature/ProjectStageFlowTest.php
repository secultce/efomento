<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Exceptions\Domain\BusinessRuleException;
use App\Exceptions\Domain\DomainAuthorizationException;
use App\Exceptions\Domain\StageTransitionException;
use App\Models\BudgetAllocation;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\ProfileSnapshot;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Services\ProjectStageService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function createValidOpening(Project $project): Opening
    {
        $opening = Opening::factory()->create([
            'project_id' => $project->id,
            'creditor_number' => '123456',
            'bank' => 'Banco do Brasil',
            'account_type' => AccountType::cases()[0]->value,
            'branch' => '1234',
            'account' => '12345-6',
            'opening_nup' => '12345678901234567',
        ]);

        OpeningSupervisor::create([
            'opening_id' => $opening->id,
            'user_id' => User::factory()->create()->id,
            'type' => 'principal',
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        OpeningSupervisor::create([
            'opening_id' => $opening->id,
            'user_id' => User::factory()->create()->id,
            'type' => 'alternate',
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        return $opening;
    }

    private function createValidFormalization(Project $project): void
    {
        $formalization = $project->formalizations()->create([
            'term_number' => '001/2026',
            'signed_by_office_at' => now(),
            'sacc_number' => '12345',
            'official_gazette_published_at' => now(),
            'validity_start_at' => now(),
            'validity_end_at' => now()->addYear(),
        ]);

        $formalization->files()->create([
            'mime_type' => 'application/pdf',
            'name' => 'gazette.pdf',
            'source' => 'upload',
            'grp' => 'official_gazette',
            'title' => 'Anexo do documento do Diário Oficial do Estado',
            'path' => 'projects/gazette.pdf',
            'private' => true,
        ]);

        foreach (DocumentType::requiredForFormalizationAdvance() as $type) {
            $project->documents()->create([
                'notice_id' => $project->notice_id,
                'created_by' => User::factory()->create()->id,
                'phase' => DocumentPhase::FORMALIZATION->value,
                'type' => $type->value,
                'name' => $type->fullLabel(),
            ]);
        }
    }

    private function createValidBudget(Project $project): void
    {
        $userId = User::factory()->create()->id;

        $budget = $project->budgets()->create([
            'created_by' => $userId,
        ]);

        $budget->installments()->create([
            'installment_number' => $project->current_installment_cycle ?? 1,
            'notice_installment_number' => 1,
            'amount' => 1000.00,
            'created_by' => $userId,
        ]);

        $project->documents()->create([
            'notice_id' => $project->notice_id,
            'created_by' => $userId,
            'phase' => DocumentPhase::BUDGET->value ?? 'budget',
            'type' => DocumentType::DO->value ?? 'do',
            'name' => 'Despacho Orçamentário',
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

        $this->createValidOpening($project);
        $this->createValidFormalization($project);
        $this->createValidBudget($project);

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

    public function test_approving_budget_links_the_allocation_for_the_agent_macroregion(): void
    {
        $project = Project::factory()->create(['current_installment_cycle' => 2]);
        $this->createValidOpening($project);
        $this->createValidBudget($project);
        ProfileSnapshot::factory()->create([
            'object_id' => $project->agent_id,
            'object_type' => 'agent',
            'city' => 'Crato',
        ]);

        $previousAllocation = BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'planning_macroregion' => '02 – CENTRO SUL',
        ]);
        $matchingAllocation = BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'planning_macroregion' => '01 – CARIRI',
        ]);
        BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'planning_macroregion' => '03 – GRANDE FORTALEZA',
        ]);

        $budget = $project->budgets;
        $previousInstallment = $budget->installments()->create([
            'budget_allocation_id' => $previousAllocation->id,
            'installment_number' => 1,
            'notice_installment_number' => 1,
            'amount' => 500,
            'created_by' => User::factory()->create()->id,
        ]);

        $stage = $this->activateStage($project, ProjectStageSlug::ORCAMENTO);
        $this->service->advance($stage, $this->createUserWithRoles('budgetary'));

        $currentInstallment = $budget->installments()
            ->where('installment_number', 2)
            ->firstOrFail();

        $this->assertTrue($currentInstallment->budgetAllocation->is($matchingAllocation));
        $this->assertTrue($previousInstallment->fresh()->budgetAllocation->is($previousAllocation));
    }

    public function test_advance_throws_when_user_lacks_role(): void
    {
        $project = Project::factory()->create();
        $first = $project->stages()->where('order', 1)->first();
        $user = User::factory()->create();

        $this->expectException(DomainAuthorizationException::class);
        $this->expectExceptionMessage('Você não tem permissão para tramitar esta etapa.');

        $this->service->advance($first, $user);
    }

    public function test_reject_throws_when_user_lacks_role(): void
    {
        $project = Project::factory()->create();
        $first = $project->stages()->where('order', 1)->first();
        $user = User::factory()->create();

        $this->expectException(DomainAuthorizationException::class);
        $this->expectExceptionMessage('Você não tem permissão para tramitar esta etapa.');

        $this->service->reject($first, 'Motivo', $user);
    }

    public function test_advance_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();
        $second = $project->stages()->where('order', 2)->first();
        $user = $this->createUserWithRoles('legal_analysis');

        $this->expectException(StageTransitionException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser aprovada.');

        $this->service->advance($second, $user);
    }

    public function test_service_reject_throws_when_stage_not_em_andamento(): void
    {
        $project = Project::factory()->create();
        $second = $project->stages()->where('order', 2)->first();
        $user = $this->createUserWithRoles('legal_analysis');

        $this->expectException(StageTransitionException::class);
        $this->expectExceptionMessage('A etapa precisa estar em andamento para ser rejeitada.');

        $this->service->reject($second, 'Motivo', $user);
    }

    public function test_reject_flow_blocks_all_subsequent_stages(): void
    {
        $project = Project::factory()->create();
        $this->createValidOpening($project);

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
        $this->createValidOpening($project);

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

        $this->expectException(DomainAuthorizationException::class);
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

        $this->expectException(BusinessRuleException::class);
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

        $this->expectException(BusinessRuleException::class);
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

        $this->expectException(StageTransitionException::class);
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
