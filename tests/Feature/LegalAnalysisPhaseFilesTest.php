<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectRegistrationPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalAnalysisPhaseFilesTest extends TestCase
{
    use RefreshDatabase;

    private function phase(Project $project, int $order): ProjectRegistrationPhase
    {
        return ProjectRegistrationPhase::create([
            'project_id' => $project->id,
            'registration_id' => 100 + $order,
            'phase_order' => $order,
            'is_first' => $order === 1,
            'status' => 10,
            'data' => ['sections' => []],
            'content_hash' => str_repeat('a', 64),
            'fetched_at' => now(),
        ]);
    }

    private function mapasFile(string $type, int $objectId, string $externalId, array $extra = []): File
    {
        return File::factory()->create(array_merge([
            'object_type' => $type,
            'object_id' => $objectId,
            'source' => 'mapas',
            'external_id' => $externalId,
            'grp' => "rfc_{$externalId}",
            'title' => "Campo {$externalId}",
            'name' => "{$externalId}.pdf",
            'path' => "projects/1/mapas/{$externalId}.pdf",
            'private' => true,
        ], $extra));
    }

    public function test_index_groups_project_and_phase_files_by_phase(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $phase2 = $this->phase($project, 2);
        $phase3 = $this->phase($project, 3);

        $this->mapasFile('project', $project->id, '1');
        $this->mapasFile('project_registration_phase', $phase3->id, '3');
        $this->mapasFile('project_registration_phase', $phase2->id, '2');
        $this->mapasFile('project', Project::factory()->create()->id, '99');

        $groups = $this->getJson(route('legal-analysis.index', $project))
            ->assertOk()
            ->json('groups');

        $this->assertSame(['Fase 1', 'Fase 2', 'Fase 3'], array_column($groups, 'group'));
        $this->assertSame(['Campo 1'], array_column($groups[0]['files'], 'title'));
        $this->assertSame(['Campo 2'], array_column($groups[1]['files'], 'title'));
    }

    public function test_index_prefers_project_row_when_phase_one_file_is_duplicated(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $phase1 = $this->phase($project, 1);

        $legacy = $this->mapasFile('project', $project->id, '7');
        $this->mapasFile('project_registration_phase', $phase1->id, '7');

        $groups = $this->getJson(route('legal-analysis.index', $project))->json('groups');

        $this->assertCount(1, $groups);
        $this->assertSame([$legacy->id], array_column($groups[0]['files'], 'id'));
    }

    public function test_phase_file_can_be_served_and_evaluated(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2);
        $file = $this->mapasFile('project_registration_phase', $phase->id, '5');
        Storage::disk('public')->put($file->path, '%PDF-1.4 conteudo');

        $this->get(route('legal-analysis.files.serve', [$project, $file]))->assertOk();

        $this->putJson(route('legal-analysis.update-status', [$project, $file]), ['status' => 'valid'])
            ->assertOk();

        $this->assertDatabaseHas('legal_analysis_files', ['file_id' => $file->id]);
    }

    public function test_file_from_another_project_returns_404(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $file = $this->mapasFile('project_registration_phase', $this->phase($other, 2)->id, '8');

        $this->get(route('legal-analysis.files.serve', [$project, $file]))->assertNotFound();
        $this->putJson(route('legal-analysis.update-status', [$project, $file]), ['status' => 'valid'])
            ->assertNotFound();
    }

    public function test_html_is_forced_to_download_and_never_inline(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
        $project = Project::factory()->create();
        $file = $this->mapasFile('project', $project->id, '9', ['name' => 'x.html', 'path' => 'projects/1/mapas/9.html']);
        Storage::disk('public')->put($file->path, '<script>alert(1)</script>');

        $response = $this->get(route('legal-analysis.files.serve', [$project, $file]));

        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }
}
