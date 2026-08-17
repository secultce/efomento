<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

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
            ->post(route('projects.create-document'), [
                'selected_projects' => [$project->id],
                'content' => 'Comunicação interna de teste',
                'type' => 'ci',
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

        $this->post(route('projects.create-document'), [
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
            ->post(route('projects.create-document'), [
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
    public function it_downloads_an_individual_document_as_docx(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => '<p>Conteúdo <strong>editável</strong></p>'
                .'<table><thead><tr><th>Campo</th><th>Valor</th></tr></thead>'
                .'<tbody><tr><td>Identificador</td><td>valor-muito-longo-sem-espacos-para-validar-a-largura</td></tr></tbody></table>',
            'created_by' => $user->id,
        ]);
        $imagePath = 'documents/docx-test-header.png';
        Storage::disk('public')->put(
            $imagePath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
        $document->images()->create([
            'section' => 'header',
            'position' => 'center',
            'path' => $imagePath,
            'is_full_width' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('documents.download', [
                'document' => $document,
                'format' => 'docx',
            ]));

        $response->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            );

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $documentXml = $zip->getFromName('word/document.xml');

        $this->assertIsString($documentXml);
        $this->assertStringContainsString('Conteúdo', $documentXml);
        $this->assertStringContainsString('<w:b/>', $documentXml);
        $this->assertStringContainsString('<w:tblW w:w="5000" w:type="pct"/>', $documentXml);
        $this->assertStringContainsString('<w:tblLayout w:type="fixed"/>', $documentXml);
        $this->assertStringContainsString('<w:gridCol w:w="5103"/>', $documentXml);
        $this->assertNotFalse($zip->locateName('word/header1.xml'));
        $this->assertNotFalse($zip->locateName('word/media/header_1.png'));
        $zip->close();

        Storage::disk('public')->delete($imagePath);
    }

    #[Test]
    public function it_returns_a_zip_containing_docx_documents(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => '<p>Comunicação interna</p>',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('documents.download-zip'), [
                'project_ids' => [$project->id],
                'type' => 'ci',
                'format' => 'docx_casa_civil',
            ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/zip');

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $this->assertSame(1, $zip->numFiles);
        $this->assertStringEndsWith('.docx', $zip->getNameIndex(0));
        $this->assertStringContainsString('_CASA_CIVIL.docx', $zip->getNameIndex(0));
        $zip->close();
    }

    #[Test]
    public function it_applies_the_casa_civil_docx_profile(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => 'ci',
            'phase' => 'opening',
            'body' => '<h1 style="font-size: 30px">Título Casa Civil</h1>'
                .'<p>Texto justificado em tamanho institucional.</p>'
                .'<table><tr><th>Campo</th><th>Valor</th></tr><tr><td>Um</td><td>Dois</td></tr></table>',
            'created_by' => $user->id,
        ]);
        $imagePath = 'documents/docx-casa-civil-header.png';
        Storage::disk('public')->put(
            $imagePath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
        $document->images()->create([
            'section' => 'header',
            'position' => 'left',
            'path' => $imagePath,
            'is_full_width' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('documents.download', [
                'document' => $document,
                'format' => 'docx_casa_civil',
            ]));

        $response->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            );
        $this->assertStringContainsString(
            '_CASA_CIVIL.docx',
            (string) $response->headers->get('Content-Disposition')
        );

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $stylesXml = (string) $zip->getFromName('word/styles.xml');
        $headerXml = (string) $zip->getFromName('word/header1.xml');

        $this->assertStringContainsString(
            '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>',
            $documentXml
        );
        $this->assertStringContainsString('<w:sz w:val="16"/><w:szCs w:val="16"/>', $documentXml);
        $this->assertStringNotContainsString('<w:sz w:val="60"/>', $documentXml);
        $this->assertStringContainsString(
            '<w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="auto"/>',
            $documentXml
        );
        $this->assertStringContainsString(
            '<w:pgMar w:top="1440" w:right="992" w:bottom="1440" w:left="993" w:header="720" w:footer="720" w:gutter="0"/>',
            $documentXml
        );
        $this->assertStringContainsString('<w:gridCol w:w="4960"/>', $documentXml);
        $this->assertStringContainsString('<w:top w:val="single" w:sz="8" w:color="000000"/>', $documentXml);
        $this->assertStringContainsString('Times New Roman', $stylesXml);
        $this->assertStringContainsString('<w:sz w:val="16"/>', $stylesXml);
        $this->assertStringContainsString('<w:jc w:val="center"/>', $headerXml);
        $this->assertStringContainsString(
            '<wp:inline distT="114300" distB="114300" distL="114300" distR="114300">',
            $headerXml
        );
        $this->assertStringNotContainsString('<w:tbl>', $headerXml);
        $this->assertFalse($zip->locateName('word/footer1.xml'));
        $zip->close();

        Storage::disk('public')->delete($imagePath);
    }

    #[Test]
    public function it_rejects_an_unsupported_download_format(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('documents.download-zip'), [
                'project_ids' => [$project->id],
                'type' => 'ci',
                'format' => 'odt',
            ])
            ->assertSessionHasErrors('format');
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
            ->post(route('projects.create-document'), [
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo original',
                'type' => 'ci',
            ]);

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo atualizado',
                'type' => 'ci',
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
            ->post(route('projects.create-document'), [
                'selected_projects' => [$projectWithCI->id],
                'content' => 'CI do projeto A',
                'type' => 'ci',
            ]);

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'selected_projects' => [$projectWithCI->id],
                'content' => 'CI do projeto A atualizado',
                'type' => 'ci',
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
