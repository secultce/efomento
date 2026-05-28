<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CIDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_ci_document_after_creation_via_endpoint(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Opening::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user)
            ->post(route('projects.create-ci'), [
                'selected_projects' => [$project->id],
                'content' => 'Comunicação interna de teste',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'project_id' => $project->id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => 'Comunicação interna de teste',
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function it_requires_authentication_to_create_ci(): void
    {
        $project = Project::factory()->create();

        $this->post(route('projects.create-ci'), [
            'selected_projects' => [$project->id],
            'content' => 'Conteúdo',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function it_requires_content_to_create_ci(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.create-ci'), [
                'selected_projects' => [$project->id],
                'content' => '',
            ])
            ->assertSessionHasErrors('content');
    }

    #[Test]
    public function it_returns_zip_download_for_selected_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Opening::factory()->create(['project_id' => $project->id]);
        Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => 'Conteúdo do CI',
            'created_by' => $user->id,
        ]);

        $mockPdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $mockPdf->shouldReceive('setPaper')->withAnyArgs()->andReturnSelf();
        $mockPdf->shouldReceive('output')->andReturn('%PDF-1.4 minimal');

        Pdf::shouldReceive('loadView')->withAnyArgs()->andReturn($mockPdf);

        $response = $this->actingAs($user)
            ->post(route('documents.download-zip'), [
                'project_ids' => [$project->id],
                'type' => 'ci',
            ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip')
            ->assertHeader('Content-Disposition');
    }

    #[Test]
    public function it_requires_at_least_one_project_id_for_download(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('documents.download-zip'), [
                'project_ids' => [],
                'type' => 'ci',
            ])
            ->assertSessionHasErrors('project_ids');
    }

    #[Test]
    public function it_requires_type_for_download(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('documents.download-zip'), [
                'project_ids' => [$project->id],
            ])
            ->assertSessionHasErrors('type');
    }

    #[Test]
    public function it_requires_authentication_to_download_zip(): void
    {
        $project = Project::factory()->create();

        $this->post(route('documents.download-zip'), [
            'project_ids' => [$project->id],
            'type' => 'ci',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function it_updates_ci_body_without_creating_a_duplicate(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Opening::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user)
            ->post(route('projects.create-ci'), [
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo original',
            ]);

        $this->actingAs($user)
            ->post(route('projects.create-ci'), [
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo atualizado',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(
            1,
            Document::where('project_id', $project->id)
                ->where('type', 'ci')
                ->count()
        );

        $this->assertDatabaseHas('documents', [
            'project_id' => $project->id,
            'type' => 'ci',
            'body' => 'Conteúdo atualizado',
        ]);
    }

    #[Test]
    public function it_preserves_original_ci_when_editing_another_project(): void
    {
        $user = User::factory()->create();
        $projectWithCI = Project::factory()->create();
        $projectNew = Project::factory()->create();

        Opening::factory()->create(['project_id' => $projectWithCI->id]);
        Opening::factory()->create(['project_id' => $projectNew->id]);

        $this->actingAs($user)
            ->post(route('projects.create-ci'), [
                'selected_projects' => [$projectWithCI->id],
                'content' => 'CI do projeto A',
            ]);

        $this->actingAs($user)
            ->post(route('projects.create-ci'), [
                'selected_projects' => [$projectWithCI->id],
                'content' => 'CI do projeto A atualizado',
            ]);

        $this->assertDatabaseHas('documents', [
            'project_id' => $projectWithCI->id,
            'type' => 'ci',
            'body' => 'CI do projeto A atualizado',
        ]);

        $this->assertDatabaseMissing('documents', [
            'project_id' => $projectNew->id,
            'type' => 'ci',
        ]);
    }
}
