<?php

namespace Tests\Feature;

use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Formalization;
use App\Models\Project;
use App\Services\FormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FormalizationService $service;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();
        // teste formalização
        $this->service = app(FormalizationService::class);
        $this->disk = config('filesystems.default', 'local');
        Storage::fake($this->disk);
    }

    private function createRequiredDocuments(Project $project, array $types = [DocumentType::TC, DocumentType::ET, DocumentType::PJ]): void
    {
        foreach ($types as $type) {
            Document::factory()->create([
                'project_id' => $project->id,
                'notice_id' => $project->notice_id,
                'type' => $type,
                'phase' => DocumentPhase::FORMALIZATION,
            ]);
        }
    }

    public function test_save_official_gazette_file_stores_file_and_creates_record(): void
    {
        $formalization = Formalization::factory()->create();
        $uploaded = UploadedFile::fake()->create('publicacao-doe.pdf', 100, 'application/pdf');

        $this->service->saveOfficialGazetteFile($uploaded, $formalization->project, $formalization);

        $files = $formalization->files()->where('grp', 'official_gazette')->get();

        $this->assertCount(1, $files);

        $file = $files->first();
        $this->assertSame('publicacao-doe.pdf', $file->name);
        $this->assertSame('upload', $file->source);
        $this->assertTrue($file->private);
        $this->assertStringContainsString(
            "projects/{$formalization->project_id}/formalizations/{$formalization->id}/official-gazette",
            $file->path
        );
        Storage::disk($this->disk)->assertExists($file->path);
    }

    public function test_save_official_gazette_file_replaces_previous_file(): void
    {
        $formalization = Formalization::factory()->create();

        $first = UploadedFile::fake()->create('primeira-versao.pdf', 100, 'application/pdf');
        $this->service->saveOfficialGazetteFile($first, $formalization->project, $formalization);
        $firstPath = $formalization->files()->where('grp', 'official_gazette')->value('path');

        $second = UploadedFile::fake()->create('segunda-versao.pdf', 100, 'application/pdf');
        $this->service->saveOfficialGazetteFile($second, $formalization->project, $formalization);

        $files = $formalization->files()->where('grp', 'official_gazette')->get();

        $this->assertCount(1, $files);
        $this->assertSame('segunda-versao.pdf', $files->first()->name);
        Storage::disk($this->disk)->assertMissing($firstPath);
        Storage::disk($this->disk)->assertExists($files->first()->path);
    }

    public function test_save_official_gazette_file_does_not_touch_other_file_groups(): void
    {
        $formalization = Formalization::factory()->create();
        $otherGroupsBefore = $formalization->files()->where('grp', '!=', 'official_gazette')->count();

        $uploaded = UploadedFile::fake()->create('publicacao-doe.pdf', 100, 'application/pdf');
        $this->service->saveOfficialGazetteFile($uploaded, $formalization->project, $formalization);

        $this->assertSame(
            $otherGroupsBefore,
            $formalization->files()->where('grp', '!=', 'official_gazette')->count()
        );
    }

    public function test_delete_file_removes_from_storage_and_database(): void
    {
        $formalization = Formalization::factory()->create();
        $path = UploadedFile::fake()->create('anexo.pdf', 10)->store('formalizations', $this->disk);

        $file = $formalization->files()->create([
            'mime_type' => 'application/pdf',
            'name' => 'anexo.pdf',
            'grp' => 'official_gazette',
            'path' => $path,
            'private' => true,
        ]);

        $this->service->deleteFile($file);

        Storage::disk($this->disk)->assertMissing($path);
        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_delete_file_without_path_only_removes_database_record(): void
    {
        $formalization = Formalization::factory()->create();

        $file = $formalization->files()->create([
            'mime_type' => 'application/pdf',
            'name' => 'sem-arquivo.pdf',
            'grp' => 'official_gazette',
            'path' => null,
            'private' => true,
        ]);

        $this->service->deleteFile($file);

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_ensure_can_advance_throws_when_formalization_is_missing(): void
    {
        $project = Project::factory()->create();

        try {
            $this->service->ensureCanAdvance($project);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('formalization', $e->errors());
            $this->assertStringContainsString(
                'Preencha e salve os dados da formalização',
                $e->errors()['formalization'][0]
            );
        }
    }

    public function test_ensure_can_advance_throws_when_required_fields_are_missing(): void
    {
        $formalization = Formalization::factory()->create(['term_number' => '']);

        try {
            $this->service->ensureCanAdvance($formalization->project);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('formalization', $e->errors());
            $this->assertStringContainsString('Número do termo', $e->errors()['formalization'][0]);
        }
    }

    public function test_ensure_can_advance_throws_when_required_documents_are_missing(): void
    {
        $formalization = Formalization::factory()->create();

        try {
            $this->service->ensureCanAdvance($formalization->project);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('documents', $e->errors());
            $message = $e->errors()['documents'][0];
            $this->assertStringContainsString('Termo de Execução Cultural', $message);
            $this->assertStringContainsString('Extrato', $message);
            $this->assertStringContainsString('Parecer Jurídico', $message);
        }
    }

    public function test_ensure_can_advance_lists_only_missing_documents(): void
    {
        $formalization = Formalization::factory()->create();
        $this->createRequiredDocuments($formalization->project, [DocumentType::TC, DocumentType::ET]);

        try {
            $this->service->ensureCanAdvance($formalization->project);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $message = $e->errors()['documents'][0];
            $this->assertStringContainsString('Parecer Jurídico', $message);
            $this->assertStringNotContainsString('Termo de Execução Cultural', $message);
            $this->assertStringNotContainsString('Extrato', $message);
        }
    }

    public function test_ensure_can_advance_ignores_documents_from_other_phases(): void
    {
        $formalization = Formalization::factory()->create();

        foreach ([DocumentType::TC, DocumentType::ET, DocumentType::PJ] as $type) {
            Document::factory()->create([
                'project_id' => $formalization->project_id,
                'notice_id' => $formalization->project->notice_id,
                'type' => $type,
                'phase' => DocumentPhase::JURIDICAL,
            ]);
        }

        $this->expectException(ValidationException::class);

        $this->service->ensureCanAdvance($formalization->project);
    }

    public function test_ensure_can_advance_passes_when_all_requirements_are_met(): void
    {
        $formalization = Formalization::factory()->create();
        $this->createRequiredDocuments($formalization->project);

        $this->service->ensureCanAdvance($formalization->project);

        $this->assertTrue(true);
    }

    public function test_ensure_can_advance_without_cge_atende_ticket_and_deliberation(): void
    {
        $formalization = Formalization::factory()->create([
            'cge_atende_ticket' => null,
            'deliberation' => null,
        ]);
        $this->createRequiredDocuments($formalization->project);

        $this->service->ensureCanAdvance($formalization->project);

        $this->assertTrue(true);
    }
}
