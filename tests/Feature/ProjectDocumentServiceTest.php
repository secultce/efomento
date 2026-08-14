<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Document;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\ProfileSnapshot;
use App\Models\Project;
use App\Models\User;
use App\Services\Documents\DocumentPlaceholderResolver;
use App\Services\ProjectDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_and_updates_a_single_notice_level_pi(): void
    {
        $user = User::factory()->create();
        $notice = Notice::factory()->create();
        $this->actingAs($user);

        $service = new ProjectDocumentService;

        $service->createNoticeDocument(
            $notice,
            DocumentType::PI,
            'Conteúdo inicial',
        );
        $service->createNoticeDocument(
            $notice,
            DocumentType::PI,
            'Conteúdo atualizado',
        );

        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseHas('documents', [
            'notice_id' => $notice->id,
            'project_id' => null,
            'type' => DocumentType::PI->value,
            'phase' => DocumentType::PI->phase()->value,
            'body' => 'Conteúdo atualizado',
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function it_creates_ci_when_project_has_no_ci()
    {

        $user = User::factory()->create();

        $this->actingAs($user);

        $project = Project::factory()->create();

        Opening::factory()->create([
            'project_id' => $project->id,
        ]);

        $service = new ProjectDocumentService;

        $service->createDocument(
            DocumentType::from('ci'),
            [$project->id],
            'Teste CI',
        );

        $this->assertDatabaseHas('documents', [
            'project_id' => $project->id,
            'type' => 'ci',
            'phase' => 'opening',
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function it_chooses_between_create_and_update_ci_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $projectWithCI = Project::factory()->create();

        Opening::factory()->create([
            'project_id' => $projectWithCI->id,
        ]);

        $existingDoc = Document::factory()->create([
            'project_id' => $projectWithCI->id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => 'Conteúdo antigo',
            'created_by' => $user->id,
        ]);

        $projectWithoutCI = Project::factory()->create();

        Opening::factory()->create([
            'project_id' => $projectWithoutCI->id,
        ]);

        $service = new ProjectDocumentService;

        $service->createDocument(
            DocumentType::from('ci'),
            [$projectWithCI->id, $projectWithoutCI->id],
            'Novo conteúdo'
        );

        $this->assertDatabaseCount(
            'documents',
            Document::whereIn('project_id', [
                $projectWithCI->id,
                $projectWithoutCI->id,
            ])->count()
        );

        $this->assertDatabaseHas('documents', [
            'id' => $existingDoc->id,
            'body' => 'Novo conteúdo',
        ]);

        $this->assertDatabaseHas('documents', [
            'project_id' => $projectWithoutCI->id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => 'Novo conteúdo',
            'created_by' => $user->id,
        ]);

        $this->assertEquals(
            1,
            Document::where('project_id', $projectWithCI->id)
                ->where('type', 'ci')
                ->count()
        );
    }

    #[Test]
    public function it_throws_exception_when_content_is_empty()
    {
        $this->expectException(\Exception::class);

        $service = new ProjectDocumentService;

        $project = Project::factory()->create();

        $service->createDocument(DocumentType::from('ci'), [$project->id], '');
    }

    #[Test]
    public function it_throws_exception_when_project_array_is_empty()
    {
        $this->expectException(\Exception::class);

        $service = new ProjectDocumentService;

        $project = Project::factory()->create();

        $service->createDocument(DocumentType::from('ci'), [], 'Teste CI');
    }

    #[Test]
    public function it_keeps_the_budget_allocation_placeholder_when_creating_documents_in_bulk()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create([
            'notice_id' => $projectA->notice_id,
        ]);

        $allocation = BudgetAllocation::factory()->create([
            'notice_id' => $projectA->notice_id,
            'management_unit' => 'UNIDADE DO EDITAL',
        ]);

        (new ProjectDocumentService)->createDocument(
            DocumentType::PI,
            [$projectB->id, $projectA->id],
            '[budget_allocation_data]',
        );

        $documents = Document::query()
            ->whereIn('project_id', [$projectA->id, $projectB->id])
            ->where('type', DocumentType::PI)
            ->get();

        $this->assertCount(2, $documents);
        $documents->each(function (Document $document) {
            $this->assertSame('[budget_allocation_data]', $document->body);
            $this->assertStringContainsString(
                'UNIDADE DO EDITAL',
                (new DocumentPlaceholderResolver)->resolve($document),
            );
        });

        $allocation->update(['management_unit' => 'UNIDADE DO EDITAL ATUALIZADA']);

        $documents->each(function (Document $document) {
            $this->assertStringContainsString(
                'UNIDADE DO EDITAL ATUALIZADA',
                (new DocumentPlaceholderResolver)->resolve($document->fresh()),
            );
        });
    }

    #[Test]
    public function it_builds_the_budget_result_table_with_all_selected_projects(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $projectA = Project::factory()->create([
            'registration_id' => 'on-1977651014',
        ]);
        $projectB = Project::factory()->create([
            'notice_id' => $projectA->notice_id,
            'registration_id' => 'on-363773451',
        ]);

        $projectA->agent()->update(['name' => 'ADELINO DO NASCIMENTO ABREU']);
        $projectB->agent()->update(['name' => 'ANA CRISTINA SOUSA MARCELINO']);

        ProfileSnapshot::factory()->create([
            'object_id' => $projectA->agent_id,
            'object_type' => 'agent',
            'city' => 'TAMBORIL',
            'recorded_at' => now(),
        ]);
        ProfileSnapshot::factory()->create([
            'object_id' => $projectB->agent_id,
            'object_type' => 'agent',
            'city' => 'JUAZEIRO DO NORTE',
            'recorded_at' => now(),
        ]);

        Opening::factory()->create([
            'project_id' => $projectA->id,
            'allocation_code' => '29196',
            'allocation_number' => '27200004.13.391.132.11689.12.339048.1.7591200070.1',
        ]);
        Opening::factory()->create([
            'project_id' => $projectB->id,
            'allocation_code' => '22601',
            'allocation_number' => '27200004.13.391.132.11689.01.339048.1.7591200070.1',
        ]);

        (new ProjectDocumentService)->createDocument(
            DocumentType::PF,
            [$projectB->id, $projectA->id],
            '[budget_result_table]',
        );

        $documents = Document::query()
            ->whereIn('project_id', [$projectA->id, $projectB->id])
            ->where('type', DocumentType::PF)
            ->get();

        $this->assertCount(2, $documents);
        $documents->each(function (Document $document) {
            $this->assertStringNotContainsString('[budget_result_table]', $document->body);
            $this->assertStringContainsString('<!-- budget_result_table:start -->', $document->body);
            $this->assertStringContainsString('data-document-placeholder="budget_result_table"', $document->body);
            $this->assertStringContainsString('<!-- budget_result_table:end -->', $document->body);
            $this->assertStringContainsString('CÓDIGO<br>INSCRIÇÃO<br>MAPAS', $document->body);
            $this->assertStringContainsString('on-363773451', $document->body);
            $this->assertStringContainsString('ANA CRISTINA SOUSA MARCELINO', $document->body);
            $this->assertStringContainsString('JUAZEIRO DO NORTE', $document->body);
            $this->assertStringContainsString('22601', $document->body);
            $this->assertStringContainsString(
                '27200004.13.391.132.11689.01.339048.1.7591200070.1',
                $document->body,
            );
            $this->assertLessThan(
                strpos($document->body, 'on-1977651014'),
                strpos($document->body, 'on-363773451'),
            );
        });
    }

    #[Test]
    public function it_uses_the_latest_notice_allocation_when_a_project_has_no_linked_allocation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $notice = Notice::factory()->create();
        $projectWithLinkedAllocation = Project::factory()->create(['notice_id' => $notice->id]);
        $projectWithUnlinkedBudget = Project::factory()->create(['notice_id' => $notice->id]);
        $projectWithoutBudget = Project::factory()->create(['notice_id' => $notice->id]);

        BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'allocation_code' => 'OLD-NOTICE-CODE',
            'allocation_number' => 'OLD-NOTICE-NUMBER',
        ]);
        $linkedAllocation = BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'allocation_code' => 'LINKED-CODE',
            'allocation_number' => 'LINKED-NUMBER',
        ]);
        BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'allocation_code' => 'LATEST-NOTICE-CODE',
            'allocation_number' => 'LATEST-NOTICE-NUMBER',
        ]);

        Budget::factory()->create([
            'project_id' => $projectWithLinkedAllocation->id,
            'budget_allocation_id' => $linkedAllocation->id,
        ]);
        Budget::factory()->create([
            'project_id' => $projectWithUnlinkedBudget->id,
            'budget_allocation_id' => null,
        ]);

        foreach ([$projectWithUnlinkedBudget, $projectWithoutBudget] as $project) {
            Opening::factory()->create([
                'project_id' => $project->id,
                'allocation_code' => 'OPENING-CODE',
                'allocation_number' => 'OPENING-NUMBER',
            ]);
        }

        (new ProjectDocumentService)->createDocument(
            DocumentType::PF,
            [
                $projectWithLinkedAllocation->id,
                $projectWithUnlinkedBudget->id,
                $projectWithoutBudget->id,
            ],
            '[budget_result_table]',
        );

        $body = Document::query()
            ->where('project_id', $projectWithLinkedAllocation->id)
            ->where('type', DocumentType::PF)
            ->value('body');

        $this->assertStringContainsString('LINKED-CODE', $body);
        $this->assertStringContainsString('LINKED-NUMBER', $body);
        $this->assertSame(2, substr_count($body, 'LATEST-NOTICE-CODE'));
        $this->assertSame(2, substr_count($body, 'LATEST-NOTICE-NUMBER'));
        $this->assertStringNotContainsString('OLD-NOTICE-CODE', $body);
        $this->assertStringNotContainsString('OPENING-CODE', $body);
    }
}
