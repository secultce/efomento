<?php

namespace Tests\Feature\Project;

use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    #[Test]
    public function guest_cannot_store_monitoring(): void
    {
        $this->postJson(route('projects.monitorings.store', $this->project))
            ->assertUnauthorized();
    }

    #[Test]
    public function authenticated_user_can_create_monitoring(): void
    {
        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [
                    ['suite_number' => '1234', 'processing_date' => '2024-03-15'],
                ],
                'observations' => 'Observação de teste',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('monitorings', [
            'project_id' => $this->project->id,
            'observations' => 'Observação de teste',
            'created_by' => $this->user->id,
        ]);

        $monitoring = $this->project->fresh()->monitoring;
        $this->assertCount(1, $monitoring->technical_opinions);
        $this->assertEquals('1234', $monitoring->technical_opinions[0]['suite_number']);
    }

    #[Test]
    public function store_uses_update_or_create_when_monitoring_already_exists(): void
    {
        Monitoring::factory()->for($this->project)->create([
            'observations' => 'Observação antiga',
        ]);

        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [],
                'observations' => 'Observação nova',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('monitorings', 1);
        $this->assertDatabaseHas('monitorings', [
            'project_id' => $this->project->id,
            'observations' => 'Observação nova',
        ]);
    }

    #[Test]
    public function authenticated_user_can_update_monitoring(): void
    {
        $monitoring = Monitoring::factory()->for($this->project)->create([
            'technical_opinions' => [['suite_number' => '0001', 'processing_date' => '2024-01-10']],
            'observations' => 'Antes',
        ]);

        $this->actingAs($this->user)
            ->patch(route('projects.monitorings.update', [$this->project, $monitoring]), [
                'technical_opinions' => [
                    ['suite_number' => '0001', 'processing_date' => '2024-01-10'],
                    ['suite_number' => '0002', 'processing_date' => '2024-06-20'],
                ],
                'observations' => 'Depois',
            ])
            ->assertRedirect();

        $fresh = $monitoring->fresh();
        $this->assertCount(2, $fresh->technical_opinions);
        $this->assertEquals('Depois', $fresh->observations);
    }

    #[Test]
    public function store_validates_suite_number_is_required_when_opinions_present(): void
    {
        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [
                    ['suite_number' => '', 'processing_date' => '2024-03-15'],
                ],
            ])
            ->assertSessionHasErrors('technical_opinions.0.suite_number');
    }

    #[Test]
    public function store_accepts_null_observations(): void
    {
        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [],
                'observations' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('monitorings', [
            'project_id' => $this->project->id,
            'observations' => null,
        ]);
    }

    // --- files() ---

    #[Test]
    public function guest_cannot_list_monitoring_files(): void
    {
        $this->getJson(route('projects.monitorings.files', $this->project))
            ->assertUnauthorized();
    }

    #[Test]
    public function files_returns_empty_when_no_monitoring_exists(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('projects.monitorings.files', $this->project))
            ->assertOk()
            ->assertJson(['files' => []]);
    }

    #[Test]
    public function files_returns_empty_when_monitoring_has_no_files(): void
    {
        Monitoring::factory()->for($this->project)->create();

        $this->actingAs($this->user)
            ->getJson(route('projects.monitorings.files', $this->project))
            ->assertOk()
            ->assertJson(['files' => []]);
    }

    #[Test]
    public function files_returns_list_with_correct_structure(): void
    {
        $monitoring = Monitoring::factory()->for($this->project)->create();

        $monitoring->files()->create([
            'name' => 'relatorio.pdf',
            'title' => 'Relatório Final',
            'grp' => 'rfc_123',
            'mime_type' => 'application/pdf',
            'path' => 'projects/1/mapas/relatorio.pdf',
            'private' => true,
            'source' => 'mapas',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('projects.monitorings.files', $this->project))
            ->assertOk();

        $files = $response->json('files');
        $this->assertCount(1, $files);
        $this->assertSame('relatorio.pdf', $files[0]['name']);
        $this->assertSame('Relatório Final', $files[0]['title']);
        $this->assertArrayHasKey('id', $files[0]);
        $this->assertStringContainsString(
            route('projects.monitorings.files.serve', [$this->project, $files[0]['id']]),
            $files[0]['url']
        );
    }

    #[Test]
    public function files_falls_back_to_name_when_title_is_null(): void
    {
        $monitoring = Monitoring::factory()->for($this->project)->create();

        $monitoring->files()->create([
            'name' => 'sem-titulo.pdf',
            'title' => null,
            'grp' => 'rfc_123',
            'mime_type' => 'application/pdf',
            'path' => 'projects/1/mapas/sem-titulo.pdf',
            'private' => true,
            'source' => 'mapas',
        ]);

        $files = $this->actingAs($this->user)
            ->getJson(route('projects.monitorings.files', $this->project))
            ->assertOk()
            ->json('files');

        $this->assertSame('sem-titulo.pdf', $files[0]['title']);
    }

    // --- serveFile() ---

    #[Test]
    public function guest_cannot_serve_monitoring_file(): void
    {
        $monitoring = Monitoring::factory()->for($this->project)->create();
        $file = $monitoring->files()->create([
            'name' => 'doc.pdf',
            'grp' => 'rfc_123',
            'mime_type' => 'application/pdf',
            'path' => 'projects/1/mapas/doc.pdf',
            'private' => true,
            'source' => 'mapas',
        ]);

        $this->get(route('projects.monitorings.files.serve', [$this->project, $file]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function serve_file_returns_404_when_file_belongs_to_another_monitoring(): void
    {
        $otherProject = Project::factory()->create();
        $otherMonitoring = Monitoring::factory()->for($otherProject)->create();

        Storage::fake(config('efomento.file_disk', 'public'));
        $path = 'projects/99/mapas/outro.pdf';
        Storage::disk(config('efomento.file_disk', 'public'))->put($path, 'conteudo');

        $file = $otherMonitoring->files()->create([
            'name' => 'outro.pdf',
            'grp' => 'rfc_999',
            'mime_type' => 'application/pdf',
            'path' => $path,
            'private' => true,
            'source' => 'mapas',
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.monitorings.files.serve', [$this->project, $file]))
            ->assertNotFound();
    }

    #[Test]
    public function serve_file_returns_404_when_path_missing_on_disk(): void
    {
        Storage::fake(config('efomento.file_disk', 'public'));

        $monitoring = Monitoring::factory()->for($this->project)->create();
        $file = $monitoring->files()->create([
            'name' => 'inexistente.pdf',
            'grp' => 'rfc_123',
            'mime_type' => 'application/pdf',
            'path' => 'projects/1/mapas/inexistente.pdf',
            'private' => true,
            'source' => 'mapas',
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.monitorings.files.serve', [$this->project, $file]))
            ->assertNotFound();
    }

    #[Test]
    public function serve_file_streams_file_for_valid_request(): void
    {
        $disk = config('efomento.file_disk', 'public');
        Storage::fake($disk);

        $path = 'projects/1/mapas/doc.pdf';
        Storage::disk($disk)->put($path, 'conteudo-pdf');

        $monitoring = Monitoring::factory()->for($this->project)->create();
        $file = $monitoring->files()->create([
            'name' => 'doc.pdf',
            'grp' => 'rfc_123',
            'mime_type' => 'application/pdf',
            'path' => $path,
            'private' => true,
            'source' => 'mapas',
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.monitorings.files.serve', [$this->project, $file]))
            ->assertOk();
    }
}
