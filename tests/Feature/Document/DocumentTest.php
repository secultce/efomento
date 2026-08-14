<?php

namespace Tests\Feature\Document;

use App\Enums\DocumentPhase;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\Domain\BusinessRuleException;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Document;
use App\Models\DocumentImage;
use App\Models\Installment;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use App\Services\Documents\DocumentPlaceholderResolver;
use App\Services\Documents\DocumentService;
use App\Services\Documents\DocumentTypeRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $this->service = new DocumentService(new DocumentTypeRegistry, new DocumentPlaceholderResolver);
        $this->user = User::factory()->create();
        $this->notice = Notice::factory()->create();
        $this->project = Project::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Model
    // -------------------------------------------------------------------------

    public function test_document_type_enum_has_correct_values(): void
    {
        $this->assertSame('tc', DocumentType::TC->value);
        $this->assertSame('et', DocumentType::ET->value);
        $this->assertSame('pj', DocumentType::PJ->value);
        $this->assertSame('pi', DocumentType::PI->value);
        $this->assertSame('pf', DocumentType::PF->value);
        $this->assertSame('do', DocumentType::DO->value);
        $this->assertSame('dp', DocumentType::DP->value);

        $this->assertSame('draft', DocumentStatus::DRAFT->value);
        $this->assertSame('pending_signature', DocumentStatus::PENDING_SIGNATURE->value);
        $this->assertSame('signed', DocumentStatus::SIGNED->value);
    }

    public function test_document_relationships(): void
    {
        $document = Document::factory()->create([
            'notice_id' => $this->notice->id,
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
        $registry = new DocumentTypeRegistry;

        $result = $registry->resolve(DocumentType::TC, DocumentPhase::FORMALIZATION);

        $this->assertSame('Termo de Execução Cultural', $result['label']);
        $this->assertTrue($result['requires_sign']);
        $this->assertFalse($result['requires_legal']);

        $result = $registry->resolve(DocumentType::PJ, DocumentPhase::FORMALIZATION);

        $this->assertSame('Parecer Jurídico', $result['label']);
        $this->assertTrue($result['requires_sign']);
        $this->assertTrue($result['requires_legal']);

        $result = $registry->resolve(DocumentType::DO, DocumentPhase::BUDGET);

        $this->assertSame('Despacho Orçamentário', $result['label']);
        $this->assertTrue($result['requires_sign']);
        $this->assertTrue($result['requires_legal']);

        $result = $registry->resolve(DocumentType::PI, DocumentPhase::BUDGET);

        $this->assertSame('Parecer Orçamentário Inicial', $result['label']);

        $result = $registry->resolve(DocumentType::PF, DocumentPhase::BUDGET);

        $this->assertSame('Parecer Orçamentário Final', $result['label']);

        $result = $registry->resolve(DocumentType::DP, DocumentPhase::PAYMENT);

        $this->assertSame('Despacho de Pagamento', $result['label']);
        $this->assertFalse($result['requires_sign']);
        $this->assertFalse($result['requires_legal']);
    }

    public function test_registry_throws_on_invalid_combination(): void
    {
        $this->expectException(BusinessRuleException::class);

        (new DocumentTypeRegistry)->resolve(DocumentType::TC, DocumentPhase::PAYMENT);
    }

    public function test_migration_converts_legacy_document_types(): void
    {
        $budgetDocument = Document::factory()->create([
            'type' => DocumentType::DO,
            'phase' => DocumentPhase::BUDGET,
        ]);
        $paymentDocument = Document::factory()->create([
            'type' => DocumentType::DP,
            'phase' => DocumentPhase::PAYMENT,
        ]);

        DB::table('documents')
            ->where('id', $budgetDocument->id)
            ->update(['type' => 'po']);
        DB::table('documents')
            ->where('id', $paymentDocument->id)
            ->update(['type' => 'd']);

        $migration = require database_path('migrations/2026_07_24_000001_migrate_legacy_document_types.php');
        $migration->up();

        $this->assertDatabaseHas('documents', [
            'id' => $budgetDocument->id,
            'type' => DocumentType::DO->value,
        ]);
        $this->assertDatabaseHas('documents', [
            'id' => $paymentDocument->id,
            'type' => DocumentType::DP->value,
        ]);
    }

    public function test_resolver_replaces_notice_installment_number_for_current_cycle(): void
    {
        $project = Project::factory()->create([
            'current_installment_cycle' => 2,
        ]);
        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);
        Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'notice_installment_number' => 3,
        ]);
        Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'notice_installment_number' => 5,
        ]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => DocumentType::DO,
            'phase' => DocumentPhase::BUDGET,
            'body' => 'Número da parcela: [notice_installment_number]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertSame('Número da parcela: 5', $resolvedBody);
    }

    public function test_resolver_clears_notice_installment_number_when_current_installment_is_missing(): void
    {
        $project = Project::factory()->create([
            'current_installment_cycle' => 2,
        ]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => DocumentType::DO,
            'phase' => DocumentPhase::BUDGET,
            'body' => 'Número da parcela: [notice_installment_number]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertSame('Número da parcela: ', $resolvedBody);
    }

    public function test_resolver_replaces_budget_allocation_data_with_formatted_notice_data(): void
    {
        $project = Project::factory()->create();
        BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'management_unit' => '27200004 - FUNDO ESTADUAL DA CULTURA',
            'work_program' => '131 - PROMOÇÃO E DESENVOLVIMENTO DA ARTE',
            'objective' => 'Democratizar, fomentar e ampliar o acesso',
            'deliverable' => '1894 - PROJETO APOIADO',
            'budget_function' => '13 - CULTURA',
            'budget_subfunction' => '392 - DIFUSÃO CULTURAL',
            'project_activity' => '11684 - PROMOÇÃO DO EDITAL',
            'expense_element' => '339048 - OUTROS AUXÍLIOS FINANCEIROS',
            'funding_source' => '719 - TRANSFERÊNCIAS DA POLÍTICA NACIONAL',
            'mapp' => '635 - FOMENTO A PROJETOS',
            'finalistic_project' => "272040102920251 - CAPITAL\n272040103020251 - INTERIOR",
        ]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => DocumentType::PI,
            'phase' => DocumentPhase::BUDGET,
            'body' => '<p>[budget_allocation_data]</p>',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertStringNotContainsString('[budget_allocation_data]', $resolvedBody);
        $this->assertStringContainsString('<strong>Gestão/Unidade:</strong>', $resolvedBody);
        $this->assertStringContainsString('27200004 - FUNDO ESTADUAL DA CULTURA', $resolvedBody);
        $this->assertStringContainsString('<strong>Ação:</strong>', $resolvedBody);
        $this->assertStringContainsString('CAPITAL<br>', $resolvedBody);
        $this->assertStringContainsString('272040103020251 - INTERIOR', $resolvedBody);
    }

    public function test_resolver_replaces_notice_fields_and_budget_data_without_a_project(): void
    {
        $notice = Notice::factory()->create([
            'name' => 'Edital das Artes',
            'nup' => '12345.678901/2026-10',
            'instrument_type' => 'TERMO DE FOMENTO',
        ]);
        BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'management_unit' => 'UNIDADE DO EDITAL',
        ]);
        $document = Document::factory()->create([
            'project_id' => null,
            'notice_id' => $notice->id,
            'type' => DocumentType::PI,
            'phase' => DocumentPhase::BUDGET,
            'body' => '[notice_name] | [nup_mother] | [finality] | [budget_allocation_data]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertStringContainsString('Edital das Artes', $resolvedBody);
        $this->assertStringContainsString('12345.678901/2026-10', $resolvedBody);
        $this->assertStringContainsString('TERMO DE FOMENTO', $resolvedBody);
        $this->assertStringContainsString('UNIDADE DO EDITAL', $resolvedBody);
        $this->assertStringNotContainsString('[budget_allocation_data]', $resolvedBody);
    }

    public function test_resolver_builds_the_budget_allocations_by_region_table_from_the_notice_import(): void
    {
        $notice = Notice::factory()->create();
        BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'region_code' => '02',
            'planning_macroregion' => 'CENTRO SUL',
            'allocation_code' => '1001424',
            'allocation_number' => '27200004.13.392.131.11687.02.339048.2.7199200000.1',
        ]);
        BudgetAllocation::factory()->create([
            'notice_id' => $notice->id,
            'region_code' => '01',
            'planning_macroregion' => 'CARIRI',
            'allocation_code' => '1001789',
            'allocation_number' => '27200004.13.392.131.11687.01.339048.2.7199200000.1',
        ]);
        $document = Document::factory()->create([
            'project_id' => null,
            'notice_id' => $notice->id,
            'type' => DocumentType::PI,
            'phase' => DocumentPhase::BUDGET,
            'body' => '[budget_allocations_by_region_table]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertStringNotContainsString('[budget_allocations_by_region_table]', $resolvedBody);
        $this->assertStringContainsString('<table', $resolvedBody);
        $this->assertStringContainsString('Macrorregião de Planejamento', $resolvedBody);
        $this->assertStringContainsString('Dotações', $resolvedBody);
        $this->assertStringContainsString('01 – CARIRI', $resolvedBody);
        $this->assertStringContainsString(
            '1001789 - 27200004.13.392.131.11687.01.339048.2.7199200000.1',
            $resolvedBody,
        );
        $this->assertLessThan(
            strpos($resolvedBody, '02 – CENTRO SUL'),
            strpos($resolvedBody, '01 – CARIRI'),
        );
    }

    public function test_resolver_escapes_budget_allocation_values(): void
    {
        $project = Project::factory()->create();
        BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'objective' => '<script>alert("xss")</script>',
        ]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'body' => '[budget_allocation_data]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertStringNotContainsString('<script>', $resolvedBody);
        $this->assertStringContainsString('&lt;script&gt;', $resolvedBody);
    }

    public function test_resolver_prefers_the_projects_linked_budget_allocation(): void
    {
        $project = Project::factory()->create();
        BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'management_unit' => 'PRIMEIRA LINHA DO EDITAL',
        ]);
        $linkedAllocation = BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'management_unit' => 'VINCULAÇÃO DO PROJETO',
        ]);
        Budget::factory()->create([
            'project_id' => $project->id,
            'budget_allocation_id' => $linkedAllocation->id,
        ]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => DocumentType::PI,
            'phase' => DocumentPhase::BUDGET,
            'body' => '[budget_allocation_data]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertStringContainsString('VINCULAÇÃO DO PROJETO', $resolvedBody);
        $this->assertStringNotContainsString('PRIMEIRA LINHA DO EDITAL', $resolvedBody);
    }

    public function test_resolver_falls_back_to_the_first_notice_allocation(): void
    {
        $project = Project::factory()->create();
        BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'management_unit' => 'PRIMEIRA LINHA DO EDITAL',
        ]);
        BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'management_unit' => 'SEGUNDA LINHA DO EDITAL',
        ]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => DocumentType::PF,
            'phase' => DocumentPhase::BUDGET,
            'body' => '[budget_allocation_data]',
        ]);

        $resolvedBody = (new DocumentPlaceholderResolver)->resolve($document);

        $this->assertStringContainsString('PRIMEIRA LINHA DO EDITAL', $resolvedBody);
        $this->assertStringNotContainsString('SEGUNDA LINHA DO EDITAL', $resolvedBody);
    }

    // -------------------------------------------------------------------------
    // DocumentService::create
    // -------------------------------------------------------------------------

    public function test_service_creates_document(): void
    {
        [$type, $phase] = $this->getRandomTypeAndPhase();

        $document = $this->service->create([
            'type' => $type->value,
            'phase' => $phase->value,
            'notice_id' => $this->notice->id,
            'project_id' => $this->project->id,
            'body' => 'Conteúdo do documento aleatório.',
        ], $this->user->id);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertDatabaseHas('documents', [
            'type' => $type->value,
            'phase' => $phase->value,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_service_create_rejects_invalid_combination(): void
    {
        // CI só é válido com OPENING — ci+payment não existe no registry
        $this->expectException(BusinessRuleException::class);

        $this->service->create([
            'type' => DocumentType::CI->value,
            'phase' => DocumentPhase::PAYMENT->value,
            'body' => 'Inválido.',
        ], $this->user->id);
    }

    public function test_service_creates_document_with_images(): void
    {

        [$type, $phase] = $this->getRandomTypeAndPhase();

        $document = $this->service->create([
            'type' => $type->value,
            'phase' => $phase->value,
            'notice_id' => $this->notice->id,
            'project_id' => $this->project->id,
            'body' => 'Com imagens.',
            'images' => [
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
            'body' => 'Novo conteúdo.',
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
            'section' => 'header',
            'position' => 'left',
            'path' => 'img/logo.png',
        ]);
    }

    public function test_sync_images_updates_existing_slot(): void
    {
        $document = Document::factory()->create();
        DocumentImage::factory()->create([
            'document_id' => $document->id,
            'section' => 'header',
            'position' => 'left',
            'path' => 'old/path.png',
        ]);

        $this->service->update($document, [
            'images' => [
                ['section' => 'header', 'position' => 'left', 'path' => 'new/path.png'],
            ],
        ]);

        $this->assertDatabaseHas('document_images', [
            'document_id' => $document->id,
            'section' => 'header',
            'position' => 'left',
            'path' => 'new/path.png',
        ]);
        $this->assertDatabaseCount('document_images', 1);
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
        [$typeA, $phaseA] = $this->getRandomTypeAndPhase();

        $typeB = collect(DocumentType::cases())->reject(fn ($t) => $t === $typeA)->random() ?? DocumentType::DP;
        $phaseB = $typeB->phase();

        Document::factory()->count(2)->create([
            'type' => $typeA,
            'phase' => $phaseA,
        ]);
        Document::factory()->count(1)->create([
            'type' => $typeB,
            'phase' => $phaseB,
        ]);

        $result = $this->service->getByContext(type: $typeA->value, phase: $phaseA->value);

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
    // HTTP — POST /api/documents
    // -------------------------------------------------------------------------

    public function test_store_endpoint_creates_document(): void
    {
        $type = DocumentType::TC;
        $phase = $type->phase();

        $response = $this->actingAs($this->user)
            ->postJson('/api/documents', [
                'type' => $type->value,
                'phase' => $phase->value,
                'notice_id' => $this->notice->id,
                'project_id' => $this->project->id,
                'body' => 'Conteúdo via HTTP.',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => $type->value, 'phase' => $phase->value]);

        $this->assertDatabaseHas('documents', ['type' => $type->value]);
    }

    public function test_store_endpoint_returns_422_for_missing_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/documents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'phase', 'body']);
    }

    public function test_store_endpoint_returns_422_for_invalid_combination(): void
    {
        $type = DocumentType::TC;

        $invalidPhase = collect(DocumentPhase::cases())
            ->reject(fn (DocumentPhase $phase) => $phase === $type->phase())
            ->random();

        $response = $this->actingAs($this->user)
            ->postJson('/api/documents', [
                'type' => $type->value,
                'phase' => $invalidPhase->value,
                'notice_id' => $this->notice->id,
                'project_id' => $this->project->id,
                'body' => 'Combinação inválida.',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => "Combinação de tipo e fase inválida: tipo={$type->value}, fase={$invalidPhase->value}."]);
    }

    // -------------------------------------------------------------------------
    // HTTP — GET /api/documents
    // -------------------------------------------------------------------------

    public function test_index_ignores_empty_string_filters(): void
    {
        Document::factory()->count(3)->create(['notice_id' => $this->notice->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/documents?type=&phase=');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    private function getRandomTypeAndPhase(): array
    {
        $type = collect(DocumentType::cases())->random();

        $phase = $type->phase();

        return [$type, $phase];
    }
}
