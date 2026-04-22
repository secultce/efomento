<?php

namespace Tests\Feature;

use App\Models\Document;
use Tests\TestCase;
use App\Models\Project;
use App\Models\Opening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ProjectDocumentService;
use PHPUnit\Framework\Attributes\Test;

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

        $service = new ProjectDocumentService();

        $service->createDocumentCI(
            [$project->id],
            'Teste CI'
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

        $service = new ProjectDocumentService();

        $service->createDocumentCI(
            [$projectWithCI->id, $projectWithoutCI->id],
            'Novo conteúdo'
        );

        $this->assertDatabaseCount(
            'documents', 
            Document::whereIn('project_id', [
                $projectWithCI->id,
                $projectWithoutCI->id
            ])->count());

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

        $service = new ProjectDocumentService();

        $project = Project::factory()->create();

        $service->createDocumentCI([$project->id], '');
    }

    #[Test]
    public function it_throws_exception_when_project_array_is_empty()
    {
        $this->expectException(\Exception::class);

        $service = new ProjectDocumentService();

        $project = Project::factory()->create();

        $service->createDocumentCI([], 'Teste CI');
    }
}