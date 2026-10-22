<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\OpeningStatus;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Agent;
use App\Models\Bank;
use App\Models\Category;
use App\Models\Document;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Projetos na Abertura com todos os campos obrigatórios preenchidos e
 * combinações diferentes de CI/TC, para testar a exigência de documentos
 * na tramitação.
 */
class CypressOpeningTramitSeeder extends Seeder
{
    public function run(): void
    {
        $scenarios = json_decode(
            file_get_contents(base_path('cypress/fixtures/openingTramit.json')),
            true
        );

        $notice = Notice::where('nup', '27001123456789012')->firstOrFail();
        $user = User::where('email', 'lara.pimentel@secult.ce.gov.br')->firstOrFail();
        $category = Category::factory()->create();

        $bank = Bank::firstOrCreate(
            ['code' => '001'],
            ['ispb' => '00000000', 'name' => 'Banco do Brasil S.A.', 'short_name' => 'BB']
        );

        $sequence = 0;

        foreach ($scenarios as $scenario) {
            $sequence++;

            $project = Project::updateOrCreate(
                ['registration_id' => $scenario['registrationId']],
                [
                    'number' => 'CYPRESS-OT-'.$sequence,
                    'category_id' => $category->id,
                    'agent_id' => Agent::factory()->create()->id,
                    'notice_id' => $notice->id,
                    'current_installment_cycle' => 1,
                    'created_by' => $user->id,
                    'title_project' => $scenario['title'],
                ]
            );

            $opening = Opening::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'opening_nup' => $scenario['nup'],
                    'opening_date' => now(),
                    'user_id' => $user->id,
                    'created_by' => $user->id,
                    'status' => OpeningStatus::EM_ANDAMENTO,
                    'is_draft' => false,
                    'creditor_number' => '123456',
                    'bank_id' => $bank->id,
                    'account_type' => 'CORRENTE',
                    'branch' => '1234',
                    'account' => '12345-6',
                ]
            );

            OpeningSupervisor::updateOrCreate(
                ['opening_id' => $opening->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL],
                [
                    'user_id' => $user->id,
                    'assigned_by' => $user->id,
                    'is_active' => true,
                    'assigned_at' => now(),
                    'removed_at' => null,
                ]
            );

            $this->setOpeningStage($project);

            Document::where('project_id', $project->id)->forceDelete();

            foreach ($scenario['documents'] as $slug) {
                $type = DocumentType::from($slug);

                Document::create([
                    'notice_id' => $notice->id,
                    'project_id' => $project->id,
                    'type' => $type,
                    'phase' => $type->phase(),
                    'body' => '<p>Documento gerado pelo Cypress</p>',
                    'status' => DocumentStatus::DRAFT,
                    'created_by' => $user->id,
                ]);
            }
        }
    }

    private function setOpeningStage(Project $project): void
    {
        $order = 1;

        foreach (ProjectStageSlug::cases() as $slug) {
            ProjectStage::updateOrCreate(
                ['project_id' => $project->id, 'slug' => $slug->value],
                [
                    'order' => $order,
                    'status' => $order === 1 ? ProjectStageStatus::EM_ANDAMENTO : ProjectStageStatus::PENDENTE,
                    'started_at' => $order === 1 ? now() : null,
                    'concluded_at' => null,
                ]
            );
            $order++;
        }
    }
}
