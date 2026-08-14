<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\BudgetAllocation;
use App\Models\Document;
use App\Models\Opening;
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
}
