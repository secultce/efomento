<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectRegistrationPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationPhaseFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['monitoring', 'coord_monitoring', 'super_admin', 'legal_analysis', 'coord_legal'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function phase(Project $project, int $order, array $fileIds = []): ProjectRegistrationPhase
    {
        $fields = array_map(fn (int $id) => [
            'id' => $id,
            'type' => 'file',
            'title' => "Documento {$id}",
            'value' => [['id' => $id, 'url' => "file/privateFile/{$id}/", 'name' => "{$id}.pdf"]],
        ], $fileIds);

        return ProjectRegistrationPhase::create([
            'project_id' => $project->id,
            'registration_id' => 100 + $order,
            'phase_order' => $order,
            'is_first' => $order === 1,
            'status' => 10,
            'data' => ['sections' => [['title' => 'Docs', 'fields' => $fields]]],
            'content_hash' => str_repeat('a', 64),
            'fetched_at' => now(),
        ]);
    }

    private function mapasFile(string $ownerType, int $ownerId, int $externalId, array $extra = []): File
    {
        return File::factory()->create(array_merge([
            'object_type' => $ownerType,
            'object_id' => $ownerId,
            'source' => 'mapas',
            'external_id' => (string) $externalId,
            'grp' => "rfc_{$externalId}",
            'title' => "Documento {$externalId}",
            'name' => "{$externalId}.pdf",
            'mime_type' => 'application/pdf',
            'path' => "projects/1/mapas/{$externalId}.pdf",
            'private' => true,
        ], $extra));
    }

    public function test_show_maps_downloaded_phase_files_to_local_routes(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2, [555]);
        $file = $this->mapasFile('project_registration_phase', $phase->id, 555);

        $this->actingAs(User::factory()->create())
            ->get(route('notices.projects.show', [$project->notice, $project]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('project.registration_phases.0.local_files.0.id', $file->id)
                ->where('project.registration_phases.0.local_files.0.view_url',
                    route('projects.registration-phases.files.serve', [$project, $phase, $file]))
                ->where('project.registration_phases.0.local_files.0.download_url',
                    route('projects.registration-phases.files.serve', [$project, $phase, $file, 'download' => 1]))
            );
    }

    public function test_show_leaves_local_files_empty_when_file_was_not_downloaded(): void
    {
        $project = Project::factory()->create();
        $this->phase($project, 2, [555]);

        $this->actingAs(User::factory()->create())
            ->get(route('notices.projects.show', [$project->notice, $project]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('project.registration_phases.0.local_files', [])
            );
    }

    public function test_show_finds_phase_one_file_owned_by_project(): void
    {
        $project = Project::factory()->create();
        $phase = $this->phase($project, 1, [777]);
        $file = $this->mapasFile('project', $project->id, 777);

        $this->actingAs(User::factory()->create())
            ->get(route('notices.projects.show', [$project->notice, $project]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('project.registration_phases.0.local_files.0.id', $file->id)
                ->where('project.registration_phases.0.local_files.0.view_url',
                    route('projects.registration-phases.files.serve', [$project, $phase, $file]))
            );
    }

    public function test_pdf_is_served_inline_and_download_forces_attachment(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2, [555]);
        $file = $this->mapasFile('project_registration_phase', $phase->id, 555);
        Storage::disk('public')->put($file->path, '%PDF-1.4 conteudo');

        $this->actingAs(User::factory()->create());

        $this->get(route('projects.registration-phases.files.serve', [$project, $phase, $file]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get(route('projects.registration-phases.files.serve', [$project, $phase, $file, 'download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=555.pdf');
    }

    public function test_html_is_forced_to_download_and_never_inline(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2, [556]);
        $file = $this->mapasFile('project_registration_phase', $phase->id, 556, [
            'name' => 'pagina.html',
            'mime_type' => 'text/html',
            'path' => 'projects/1/mapas/556.html',
        ]);
        Storage::disk('public')->put($file->path, '<script>alert(1)</script>');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('projects.registration-phases.files.serve', [$project, $phase, $file]));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_file_from_another_project_returns_404(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2, [555]);
        $other = Project::factory()->create();
        $otherPhase = $this->phase($other, 2, [888]);
        $foreign = $this->mapasFile('project_registration_phase', $otherPhase->id, 888);
        Storage::disk('public')->put($foreign->path, '%PDF-1.4');

        $this->actingAs(User::factory()->create())
            ->get(route('projects.registration-phases.files.serve', [$project, $phase, $foreign]))
            ->assertNotFound();
    }

    public function test_file_from_another_phase_of_the_same_project_returns_404(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2, [555]);
        $otherPhase = $this->phase($project, 3, [999]);
        $file = $this->mapasFile('project_registration_phase', $otherPhase->id, 999);
        Storage::disk('public')->put($file->path, '%PDF-1.4');

        $this->actingAs(User::factory()->create())
            ->get(route('projects.registration-phases.files.serve', [$project, $phase, $file]))
            ->assertNotFound();
    }

    public function test_phase_from_another_project_returns_404(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $otherPhase = $this->phase($other, 2, [555]);
        $file = $this->mapasFile('project_registration_phase', $otherPhase->id, 555);
        Storage::disk('public')->put($file->path, '%PDF-1.4');

        $this->actingAs(User::factory()->create())
            ->get(route('projects.registration-phases.files.serve', [$project, $otherPhase, $file]))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $project = Project::factory()->create();
        $phase = $this->phase($project, 2, [555]);
        $file = $this->mapasFile('project_registration_phase', $phase->id, 555);

        $this->get(route('projects.registration-phases.files.serve', [$project, $phase, $file]))
            ->assertRedirect(route('login'));
    }
}
