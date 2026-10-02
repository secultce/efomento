<?php

namespace Database\Seeders;

use App\Enums\OpeningStatus;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Agent;
use App\Models\Budget;
use App\Models\Category;
use App\Models\File;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Database\Seeder;

class CypressProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = json_decode(
            file_get_contents(base_path('cypress/fixtures/projects.json')),
            true
        );

        $notice = Notice::where(
            'nup',
            '27001123456789012'
        )->firstOrFail();

        $user = User::where(
            'email',
            'lara.pimentel@secult.ce.gov.br'
        )->firstOrFail();

        $agent = Agent::factory()->create();

        $category = Category::factory()->create();

        /*
         * Project used by tests that start in the Opening phase.
         */
        $openingProjectData = $projects['opening'];

        $openingAgent = Agent::factory()->create();

        $openingProject = $this->createProject(
            registrationId: $openingProjectData['registrationId'],
            number: 'CYPRESS-001',
            title: $openingProjectData['title'],
            notice: $notice,
            user: $user,
            agent: $openingAgent,
            category: $category,
        );

        $this->createOpening(
            project: $openingProject,
            user: $user,
            nup: $openingProjectData['nup'],
        );

        $this->setProjectStage(
            project: $openingProject,
            currentStage: ProjectStageSlug::ABERTURA,
        );

        /*
         * Project used by tests that start in the Legal Analysis phase.
         */
        $legalAnalysisProjectData = $projects['legal_analisys'];

        $legalAnalisysAgent = Agent::factory()->create();

        $legalAnalysisProject = $this->createProject(
            registrationId: $legalAnalysisProjectData['registrationId'],
            number: 'CYPRESS-002',
            title: $legalAnalysisProjectData['title'],
            notice: $notice,
            user: $user,
            agent: $legalAnalisysAgent,
            category: $category,
        );

        $this->createOpening(
            project: $legalAnalysisProject,
            user: $user,
            nup: $legalAnalysisProjectData['nup'],
        );

        $this->setProjectStage(
            project: $legalAnalysisProject,
            currentStage: ProjectStageSlug::ANALISE_JURIDICA,
        );

        $this->createLegalAnalysisFiles(
            project: $legalAnalysisProject,
        );

        /*
         * Project used by tests that start in the Formalization phase.
         */
        $formalizationProjectData = $projects['formalization'];

        $formalizationAgent = Agent::factory()->create();

        $formalizationProject = $this->createProject(
            registrationId: $formalizationProjectData['registrationId'],
            number: 'CYPRESS-003',
            title: $formalizationProjectData['title'],
            notice: $notice,
            user: $user,
            agent: $formalizationAgent,
            category: $category,
        );

        $this->createOpening(
            project: $formalizationProject,
            user: $user,
            nup: $formalizationProjectData['nup'],
        );

        $this->setProjectStage(
            project: $formalizationProject,
            currentStage: ProjectStageSlug::FORMALIZACAO,
        );

        $this->createLegalAnalysisFiles(
            project: $formalizationProject,
        );

        /*
         * Project used by tests that start in the Budgetary phase.
         */
        $budgetaryProjectData = $projects['budgetary'];

        $budgetaryAgent = Agent::factory()->create();

        $budgetaryProject = $this->createProject(
            registrationId: $budgetaryProjectData['registrationId'],
            number: 'CYPRESS-004',
            title: $budgetaryProjectData['title'],
            notice: $notice,
            user: $user,
            agent: $budgetaryAgent,
            category: $category,
        );

        $this->createOpening(
            project: $budgetaryProject,
            user: $user,
            nup: $budgetaryProjectData['nup'],
        );

        $this->setProjectStage(
            project: $budgetaryProject,
            currentStage: ProjectStageSlug::ORCAMENTO,
        );

        $this->createLegalAnalysisFiles(
            project: $budgetaryProject,
        );

        /*
         * Project used by tests that start in the Payment phase.
         */
        $paymentProjectData = $projects['payment'];

        $paymentAgent = Agent::factory()->create();

        $paymentProject = $this->createProject(
            registrationId: $paymentProjectData['registrationId'],
            number: 'CYPRESS-005',
            title: $paymentProjectData['title'],
            notice: $notice,
            user: $user,
            agent: $paymentAgent,
            category: $category,
        );

        $this->createOpening(
            project: $paymentProject,
            user: $user,
            nup: $paymentProjectData['nup'],
        );

        $this->createBudgetInstallment(
            project: $paymentProject,
            user: $user,
            installment: $paymentProjectData['installment'],
        );

        $this->setProjectStage(
            project: $paymentProject,
            currentStage: ProjectStageSlug::PAGAMENTO,
        );

        /*
         * Project used by tests that start in the Monitoring phase.
         */
        $monitoringProjectData = $projects['monitoring'];

        $monitoringAgent = Agent::factory()->create();

        $monitoringProject = $this->createProject(
            registrationId: $monitoringProjectData['registrationId'],
            number: 'CYPRESS-006',
            title: $monitoringProjectData['title'],
            notice: $notice,
            user: $user,
            agent: $monitoringAgent,
            category: $category,
        );

        $this->createOpening(
            project: $monitoringProject,
            user: $user,
            nup: $monitoringProjectData['nup'],
        );

        $this->setProjectStage(
            project: $monitoringProject,
            currentStage: ProjectStageSlug::MONITORAMENTO,
        );
    }

    private function createProject(
        string $registrationId,
        string $number,
        string $title,
        Notice $notice,
        User $user,
        Agent $agent,
        Category $category,
    ): Project {
        return Project::updateOrCreate(
            [
                'registration_id' => $registrationId,
            ],
            [
                'number' => $number,
                'category_id' => $category->id,
                'agent_id' => $agent->id,
                'notice_id' => $notice->id,
                'current_installment_cycle' => 1,
                'created_by' => $user->id,
                'title_project' => $title,
            ]
        );
    }

    private function createOpening(
        Project $project,
        User $user,
        string $nup
    ): void {
        Opening::updateOrCreate(
            [
                'project_id' => $project->id,
            ],
            [
                'opening_nup' => $nup,
                'opening_date' => now(),
                'user_id' => $user->id,
                'created_by' => $user->id,
                'status' => OpeningStatus::EM_ANDAMENTO,
                'is_draft' => false,
            ]
        );
    }

    private function setProjectStage(
        Project $project,
        ProjectStageSlug $currentStage
    ): void {
        $stages = [
            ProjectStageSlug::ABERTURA->value => 1,
            ProjectStageSlug::ANALISE_JURIDICA->value => 2,
            ProjectStageSlug::FORMALIZACAO->value => 3,
            ProjectStageSlug::ORCAMENTO->value => 4,
            ProjectStageSlug::PAGAMENTO->value => 5,
            ProjectStageSlug::MONITORAMENTO->value => 6,
            ProjectStageSlug::PRESTACAO_DE_CONTAS->value => 7,
        ];

        $currentOrder = $stages[$currentStage->value];

        foreach ($stages as $slug => $order) {
            if ($order < $currentOrder) {
                $status = ProjectStageStatus::APROVADO;
            } elseif ($order === $currentOrder) {
                $status = ProjectStageStatus::EM_ANDAMENTO;
            } else {
                $status = ProjectStageStatus::PENDENTE;
            }

            ProjectStage::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'slug' => $slug,
                ],
                [
                    'order' => $order,
                    'status' => $status,
                    'started_at' => $order <= $currentOrder
                        ? now()
                        : null,
                    'concluded_at' => $order < $currentOrder
                        ? now()
                        : null,
                ]
            );
        }
    }

    private function createLegalAnalysisFiles(
        Project $project
    ): void {
        $files = [
            [
                'external_id' => 'CYPRESS-LEGAL-FILE-001',
                'name' => 'Documento Cypress 001.pdf',
                'title' => 'Documento Cypress 001',
            ],
            [
                'external_id' => 'CYPRESS-LEGAL-FILE-002',
                'name' => 'Documento Cypress 002.pdf',
                'title' => 'Documento Cypress 002',
            ],
            [
                'external_id' => 'CYPRESS-LEGAL-FILE-003',
                'name' => 'Documento Cypress 003.pdf',
                'title' => 'Documento Cypress 003',
            ],
        ];

        foreach ($files as $file) {
            File::updateOrCreate(
                [
                    'object_type' => 'project',
                    'object_id' => $project->id,
                    'source' => 'cypress',
                    'external_id' => $file['external_id'],
                ],
                [
                    'mime_type' => 'application/pdf',
                    'name' => $file['name'],
                    'grp' => 'cypress',
                    'title' => $file['title'],
                    'description' => 'Documento utilizado nos testes automatizados do Cypress.',
                    'path' => null,
                    'private' => true,
                ]
            );
        }
    }

    private function createBudgetInstallment(
        Project $project,
        User $user,
        array $installment,
    ): void {
        $budget = Budget::updateOrCreate(
            [
                'project_id' => $project->id,
            ],
            [
                'created_by' => $user->id,
                'processing_date_for_codip' => null,
                'processing_date_for_coafi' => null,
            ]
        );

        $budget->installments()->updateOrCreate(
            [
                'installment_number' => $project->current_installment_cycle,
            ],
            [
                'notice_installment_number' => $installment['number'],
                'amount' => $installment['amount'],
                'request_date' => now(),
                'created_by' => $user->id,

                'committed_amount' => null,
                'settlement_amount' => null,
                'payment_order_number' => null,
                'payment_amount' => null,
                'payment_date' => null,
            ]
        );
    }
}
