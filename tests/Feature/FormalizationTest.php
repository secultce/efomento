<?php

namespace Tests\Feature;

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use App\Enums\TermStatus;
use App\Models\Formalization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->disk = config('filesystems.default', 'local');
        Storage::fake($this->disk);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'report_status' => ReportStatus::REGULAR_E_ADIMPLENTE->value,
            'term_number' => 'TERM-2026-001',
            'term_status' => TermStatus::SIGNED->value,
            'signature_status_office' => TermStatus::UNSIGNED->value,
            'deliberation' => DeliberationType::MANUAL->value,
        ], $overrides);
    }

    public function test_guest_cannot_store_formalization(): void
    {
        $project = Project::factory()->create();

        $this->post(route('projects.formalizations.store', $project), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_store_creates_formalization_for_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->from(route('projects.show', $project))
            ->post(route('projects.formalizations.store', $project), $this->validPayload())
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('formalizations', [
            'project_id' => $project->id,
            'term_number' => 'TERM-2026-001',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_updates_existing_formalization_instead_of_duplicating(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), $this->validPayload());

        $this->actingAs($this->user)
            ->post(
                route('projects.formalizations.store', $project),
                $this->validPayload(['term_number' => 'TERM-2026-002'])
            );

        $this->assertSame(1, Formalization::where('project_id', $project->id)->count());
        $this->assertDatabaseHas('formalizations', [
            'project_id' => $project->id,
            'term_number' => 'TERM-2026-002',
        ]);
    }

    public function test_store_saves_official_gazette_file_when_uploaded(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), $this->validPayload([
                'official_gazette_file' => UploadedFile::fake()->create('doe.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasNoErrors();

        $formalization = Formalization::where('project_id', $project->id)->firstOrFail();
        $file = $formalization->files()->where('grp', 'official_gazette')->first();

        $this->assertNotNull($file);
        $this->assertSame('doe.pdf', $file->name);
        Storage::disk($this->disk)->assertExists($file->path);
    }

    public function test_store_requires_mandatory_fields(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), [])
            ->assertSessionHasErrors([
                'report_status',
                'term_number',
                'term_status',
                'signature_status_office',
                'deliberation',
            ]);
    }

    public function test_store_rejects_invalid_enum_values(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), $this->validPayload([
                'report_status' => 'INVALIDO',
                'term_status' => 'INVALIDO',
                'signature_status_office' => 'INVALIDO',
                'deliberation' => 'INVALIDO',
            ]))
            ->assertSessionHasErrors([
                'report_status',
                'term_status',
                'signature_status_office',
                'deliberation',
            ]);
    }

    public function test_store_rejects_validity_end_before_validity_start(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), $this->validPayload([
                'validity_start_at' => '2026-06-10',
                'validity_end_at' => '2026-06-09',
            ]))
            ->assertSessionHasErrors(['validity_end_at']);
    }

    public function test_store_rejects_official_gazette_file_with_invalid_mime(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), $this->validPayload([
                'official_gazette_file' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors(['official_gazette_file']);
    }

    public function test_store_rejects_official_gazette_file_above_max_size(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.formalizations.store', $project), $this->validPayload([
                'official_gazette_file' => UploadedFile::fake()->create('doe.pdf', 10241, 'application/pdf'),
            ]))
            ->assertSessionHasErrors(['official_gazette_file']);
    }

    public function test_update_modifies_existing_formalization(): void
    {
        $formalization = Formalization::factory()->create();

        $this->actingAs($this->user)
            ->patch(
                route('projects.formalizations.update', [$formalization->project, $formalization]),
                $this->validPayload(['term_number' => 'TERM-ATUALIZADO'])
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('formalizations', [
            'id' => $formalization->id,
            'term_number' => 'TERM-ATUALIZADO',
        ]);
    }

    public function test_update_saves_official_gazette_file_when_uploaded(): void
    {
        $formalization = Formalization::factory()->create();

        $this->actingAs($this->user)
            ->patch(
                route('projects.formalizations.update', [$formalization->project, $formalization]),
                $this->validPayload([
                    'official_gazette_file' => UploadedFile::fake()->create('doe-novo.pdf', 100, 'application/pdf'),
                ])
            )
            ->assertSessionHasNoErrors();

        $file = $formalization->files()
            ->where('grp', 'official_gazette')
            ->where('name', 'doe-novo.pdf')
            ->first();

        $this->assertNotNull($file);
        Storage::disk($this->disk)->assertExists($file->path);
    }

    public function test_update_rejects_formalization_from_another_project(): void
    {
        $formalization = Formalization::factory()->create();
        $otherProject = Project::factory()->create();

        $this->actingAs($this->user)
            ->patch(
                route('projects.formalizations.update', [$otherProject, $formalization]),
                $this->validPayload()
            )
            ->assertNotFound();
    }

    public function test_show_file_streams_file_inline(): void
    {
        $formalization = Formalization::factory()->create();
        $path = UploadedFile::fake()->create('doe.pdf', 10, 'application/pdf')
            ->store('formalizations', $this->disk);

        $file = $formalization->files()->create([
            'mime_type' => 'application/pdf',
            'name' => 'doe.pdf',
            'grp' => 'official_gazette',
            'path' => $path,
            'private' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.formalizations.files.show', [$formalization->project, $formalization, $file]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="doe.pdf"');
    }

    public function test_download_file_returns_attachment(): void
    {
        $formalization = Formalization::factory()->create();
        $path = UploadedFile::fake()->create('doe.pdf', 10, 'application/pdf')
            ->store('formalizations', $this->disk);

        $file = $formalization->files()->create([
            'mime_type' => 'application/pdf',
            'name' => 'doe.pdf',
            'grp' => 'official_gazette',
            'path' => $path,
            'private' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('projects.formalizations.files.download', [$formalization->project, $formalization, $file]))
            ->assertOk()
            ->assertDownload('doe.pdf');
    }

    public function test_destroy_file_removes_file(): void
    {
        $formalization = Formalization::factory()->create();
        $path = UploadedFile::fake()->create('doe.pdf', 10, 'application/pdf')
            ->store('formalizations', $this->disk);

        $file = $formalization->files()->create([
            'mime_type' => 'application/pdf',
            'name' => 'doe.pdf',
            'grp' => 'official_gazette',
            'path' => $path,
            'private' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('projects.formalizations.files.destroy', [$formalization->project, $formalization, $file]))
            ->assertRedirect();

        Storage::disk($this->disk)->assertMissing($path);
        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }
}
