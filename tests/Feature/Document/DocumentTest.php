<?php

namespace Tests\Feature\Document;

use App\Enums\DocumentImagePosition;
use App\Enums\DocumentImageSection;
use App\Enums\DocumentPhase;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentImage;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Documents\DocumentTypeRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $service;
    private User $user;
    private Notice $notice;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DocumentService(new DocumentTypeRegistry());
        $this->user    = User::factory()->create();
        $this->notice  = Notice::factory()->create();
        $this->project = Project::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Model
    // -------------------------------------------------------------------------

    public function test_document_type_enum_has_correct_values(): void
    {
        $this->assertSame('term',             DocumentType::TERM->value);
        $this->assertSame('extract',          DocumentType::EXTRACT->value);
        $this->assertSame('juridical_opinion',DocumentType::JURIDICAL_OPINION->value);
        $this->assertSame('dispatch',         DocumentType::DISPATCH->value);

        $this->assertSame('draft',             DocumentStatus::DRAFT->value);
        $this->assertSame('pending_signature', DocumentStatus::PENDING_SIGNATURE->value);
        $this->assertSame('signed',            DocumentStatus::SIGNED->value);
    }

    public function test_document_relationships(): void
    {
        $document = Document::factory()->create([
            'notice_id'  => $this->notice->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Notice::class, $document->notice);
        $this->assertInstanceOf(User::class, $document->creator);
        $this->assertCount(0, $document->images);
    }

    public function test_document_soft_delete(): void
    {
        $document = Document::factory()->create();

        $document->delete();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        $this->assertNotNull(Document::withTrashed()->find($document->id));
    }

    public function test_cannot_create_document_without_required_fields(): void
    {
        $this->expectException(QueryException::class);

        Document::create(['body' => 'sem type, phase ou created_by']);
    }

    // -------------------------------------------------------------------------
    // DocumentTypeRegistry
    // -------------------------------------------------------------------------

    public function test_registry_resolves_valid_combinations(): void
    {
        $registry = new DocumentTypeRegistry();

        $result = $registry->resolve(DocumentType::TERM, DocumentPhase::FORMALIZATION);

        $this->assertSame('Termo de Execução Cultural', $result['label']);
        $this->assertTrue($result['requires_sign']);
        $this->assertFalse($result['requires_legal']);

        $result = $registry->resolve(DocumentType::JURIDICAL_OPINION, DocumentPhase::JURIDICAL);

        $this->assertSame('Parecer Jurídico', $result['label']);
        $this->assertTrue($result['requires_sign']);
        $this->assertTrue($result['requires_legal']);
    }

    public function test_registry_resolves_juridical_opinion(): void
    {
        $registry = new DocumentTypeRegistry();

        $result = $registry->resolve(DocumentType::JURIDICAL_OPINION, DocumentPhase::JURIDICAL);

        $this->assertTrue($result['requires_sign']);
        $this->assertTrue($result['requires_legal']);
    }

    public function test_registry_throws_on_invalid_combination(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new DocumentTypeRegistry())->resolve(DocumentType::TERM, DocumentPhase::PAYMENT);
    }

    // -------------------------------------------------------------------------
    // DocumentService::create
    // -------------------------------------------------------------------------

    public function test_service_creates_document(): void
    {
        $document = $this->service->create([
            'type'       => 'term',
            'phase'      => 'formalization',
            'notice_id'  => $this->notice->id,
            'project_id' => $this->project->id,
            'body'       => 'Conteúdo do termo.',
        ], $this->user->id);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertDatabaseHas('documents', [
            'type'       => 'term',
            'phase'      => 'formalization',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_service_create_rejects_invalid_combination(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create([
            'type'  => 'term',
            'phase' => 'payment',
            'body'  => 'Inválido.',
        ], $this->user->id);
    }

    public function test_service_creates_document_with_images(): void
    {
        $document = $this->service->create([
            'type'       => 'term',
            'phase'      => 'formalization',
            'notice_id'  => $this->notice->id,
            'project_id' => $this->project->id,
            'body'       => 'Com imagens.',
            'images'     => [
                ['section' => 'header', 'position' => 'left',   'path' => 'logos/a.png'],
                ['section' => 'footer', 'position' => 'center', 'path' => 'logos/b.png'],
            ],
        ], $this->user->id);

        $this->assertCount(2, $document->images);
    }

    // -------------------------------------------------------------------------
    // DocumentService::update
    // -------------------------------------------------------------------------

    public function test_service_updates_body_and_status(): void
    {
        $document = Document::factory()->create(['status' => DocumentStatus::DRAFT]);

        $updated = $this->service->update($document, [
            'body'   => 'Novo conteúdo.',
            'status' => DocumentStatus::PENDING_SIGNATURE->value,
        ]);

        $this->assertSame('Novo conteúdo.', $updated->body);
        $this->assertSame(DocumentStatus::PENDING_SIGNATURE, $updated->status);
    }

    // -------------------------------------------------------------------------
    // DocumentService — sync de imagens (testado via update)
    // -------------------------------------------------------------------------

    public function test_sync_images_creates_new_slots(): void
    {
        $document = Document::factory()->create();

        $this->service->update($document, [
            'images' => [
                ['section' => 'header', 'position' => 'left', 'path' => 'img/logo.png'],
            ],
        ]);

        $this->assertDatabaseHas('document_images', [
            'document_id' => $document->id,
            'section'     => 'header',
            'position'    => 'left',
            'path'        => 'img/logo.png',
        ]);
    }

    public function test_sync_images_updates_existing_slot(): void
    {
        $document = Document::factory()->create();
        DocumentImage::factory()->create([
            'document_id' => $document->id,
            'section'     => 'header',
            'position'    => 'left',
            'path'        => 'old/path.png',
        ]);

        $this->service->update($document, [
            'images' => [
                ['section' => 'header', 'position' => 'left', 'path' => 'new/path.png'],
            ],
        ]);

        $this->assertDatabaseHas('document_images', [
            'document_id' => $document->id,
            'section'     => 'header',
            'position'    => 'left',
            'path'        => 'new/path.png',
        ]);
        $this->assertDatabaseCount('document_images', 1);
    }

    public function test_sync_images_removes_absent_slots(): void
    {
        $document = Document::factory()->create();
        DocumentImage::factory()->create([
            'document_id' => $document->id,
            'section'     => 'header',
            'position'    => 'left',
            'path'        => 'a.png',
        ]);
        DocumentImage::factory()->create([
            'document_id' => $document->id,
            'section'     => 'footer',
            'position'    => 'center',
            'path'        => 'b.png',
        ]);

        // envia apenas header/left — footer/center deve ser removido
        $this->service->update($document, [
            'images' => [
                ['section' => 'header', 'position' => 'left', 'path' => 'a.png'],
            ],
        ]);

        $this->assertDatabaseCount('document_images', 1);
        $this->assertDatabaseMissing('document_images', ['section' => 'footer']);
    }

    // -------------------------------------------------------------------------
    // DocumentService::getByContext
    // -------------------------------------------------------------------------

    public function test_get_by_context_filters_by_notice(): void
    {
        Document::factory()->count(3)->create(['notice_id' => $this->notice->id]);
        Document::factory()->count(2)->create(['notice_id' => Notice::factory()->create()->id]);

        $result = $this->service->getByContext(noticeId: $this->notice->id);

        $this->assertCount(3, $result);
    }

    public function test_get_by_context_filters_by_type_and_phase(): void
    {
        Document::factory()->count(2)->create([
            'type'  => DocumentType::TERM,
            'phase' => DocumentPhase::FORMALIZATION,
        ]);
        Document::factory()->count(1)->create([
            'type'  => DocumentType::DISPATCH,
            'phase' => DocumentPhase::JURIDICAL,
        ]);

        $result = $this->service->getByContext(type: 'term', phase: 'formalization');

        $this->assertCount(2, $result);
    }

    public function test_get_by_context_eager_loads_images(): void
    {
        $document = Document::factory()->create();
        DocumentImage::factory()->create(['document_id' => $document->id]);

        $result = $this->service->getByContext();

        $this->assertTrue($result->first()->relationLoaded('images'));
    }

    // -------------------------------------------------------------------------
    // Soft delete
    // -------------------------------------------------------------------------

    public function test_service_soft_deletes_document(): void
    {
        $document = Document::factory()->create();

        $document->delete();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    // -------------------------------------------------------------------------
    // HTTP — POST /api/documents
    // -------------------------------------------------------------------------

    public function test_store_endpoint_creates_document(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/documents', [
                'type'       => 'term',
                'phase'      => 'formalization',
                'notice_id'  => $this->notice->id,
                'project_id' => $this->project->id,
                'body'       => 'Conteúdo via HTTP.',
            ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['type' => 'term', 'phase' => 'formalization']);

        $this->assertDatabaseHas('documents', ['type' => 'term']);
    }

    public function test_store_endpoint_returns_422_for_missing_fields(): void
    {
        $response = $this->postJson('/api/documents', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type', 'phase', 'body']);
    }

    public function test_store_endpoint_returns_422_for_invalid_combination(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/documents', [
                'type'       => 'term',
                'phase'      => 'payment',
                'notice_id'  => $this->notice->id,
                'project_id' => $this->project->id,
                'body'       => 'Combinação inválida.',
            ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Combinação de tipo e fase inválida: tipo=term, fase=payment.']);
    }
}
