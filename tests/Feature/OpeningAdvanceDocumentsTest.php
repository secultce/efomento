<?php

namespace Tests\Feature;

use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\Bank;
use App\Models\Document;
use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\Project;
use App\Services\OpeningUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpeningAdvanceDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private OpeningUpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OpeningUpdateService::class);
    }

    private function createValidOpening(): Project
    {
        $bank = Bank::create(['ispb' => '00000000', 'code' => '001', 'name' => 'Banco do Brasil', 'short_name' => 'BB']);

        $opening = Opening::factory()->create([
            'bank_id' => $bank->id,
            'opening_nup' => '12345678901234567',
            'creditor_number' => '123456',
        ]);

        OpeningSupervisor::factory()->principal()->create(['opening_id' => $opening->id]);

        return $opening->project;
    }

    private function createDocument(Project $project, DocumentType $type, DocumentPhase $phase = DocumentPhase::OPENING): void
    {
        Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => $type,
            'phase' => $phase,
        ]);
    }

    private function missingDocumentsMessage(Project $project): ?string
    {
        try {
            $this->service->ensureCanAdvance($project);
        } catch (ValidationException $e) {
            return $e->errors()['documents'][0] ?? null;
        }

        return null;
    }

    public function test_required_documents_for_opening_are_ci_and_tc(): void
    {
        $this->assertSame(
            [DocumentType::CI, DocumentType::TC],
            DocumentType::requiredForOpeningAdvance()
        );
    }

    public function test_cannot_advance_without_ci_and_tc(): void
    {
        $message = $this->missingDocumentsMessage($this->createValidOpening());

        $this->assertNotNull($message);
        $this->assertStringContainsString(DocumentType::CI->fullLabel(), $message);
        $this->assertStringContainsString(DocumentType::TC->fullLabel(), $message);
    }

    public function test_cannot_advance_with_only_ci(): void
    {
        $project = $this->createValidOpening();
        $this->createDocument($project, DocumentType::CI);

        $message = $this->missingDocumentsMessage($project);

        $this->assertNotNull($message);
        $this->assertStringContainsString(DocumentType::TC->fullLabel(), $message);
        $this->assertStringNotContainsString(DocumentType::CI->fullLabel(), $message);
    }

    public function test_cannot_advance_with_only_tc(): void
    {
        $project = $this->createValidOpening();
        $this->createDocument($project, DocumentType::TC, DocumentPhase::FORMALIZATION);

        $message = $this->missingDocumentsMessage($project);

        $this->assertNotNull($message);
        $this->assertStringContainsString(DocumentType::CI->fullLabel(), $message);
        $this->assertStringNotContainsString(DocumentType::TC->fullLabel(), $message);
    }

    public function test_can_advance_with_ci_and_tc(): void
    {
        $project = $this->createValidOpening();
        $this->createDocument($project, DocumentType::CI);
        $this->createDocument($project, DocumentType::TC, DocumentPhase::FORMALIZATION);

        $this->service->ensureCanAdvance($project);

        $this->assertTrue(true);
    }
}
