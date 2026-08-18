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
            'body' => '<h2>Subtítulo</h2>'
                .'<p style="font-family: Georgia; font-size: 18px; color: rgb(18, 52, 86); background-color: #fed; text-align: right; margin-left: 20px; line-height: 1.25">'
                .'Conteúdo <strong>editável</strong><sup>2</sup></p>'
                .'<div style="font-family: Courier New; color: #654321; text-align: center"><p>Formatação herdada</p></div>'
                ."<pre>linha 1\n  linha 2</pre>"
                .'<ul><li>Primeiro item</li><li>Segundo item</li></ul>'
                .'<table style="font-size: 8px; text-align: left"><thead><tr>'
                .'<th style="width: 34%; border: 1px solid #9ca3af; padding: 6px 7px; background-color: #d9d9d9; text-align: center">Campo</th>'
                .'<th style="width: 66%; border: 1px solid #9ca3af; padding: 6px 7px; background-color: #d9d9d9; text-align: center">Valor</th>'
                .'</tr></thead><tbody><tr>'
                .'<td style="border: 1px solid #9ca3af; padding: 5px 7px">Identificador</td>'
                .'<td style="border: 1px solid #9ca3af; padding: 5px 7px">valor-muito-longo-sem-espacos-para-validar-a-largura</td>'
                .'</tr></tbody></table>',
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
        $stylesXml = $zip->getFromName('word/styles.xml');
        $numberingXml = $zip->getFromName('word/numbering.xml');

        $this->assertIsString($documentXml);
        $this->assertIsString($stylesXml);
        $this->assertIsString($numberingXml);
        $this->assertStringContainsString('Conteúdo', $documentXml);
        $this->assertStringContainsString('<w:b/>', $documentXml);
        $this->assertStringContainsString(
            '<w:rFonts w:ascii="Georgia" w:hAnsi="Georgia" w:eastAsia="Georgia" w:cs="Georgia"/>',
            $documentXml
        );
        $this->assertStringContainsString('<w:sz w:val="27"/><w:szCs w:val="27"/>', $documentXml);
        $this->assertStringContainsString('<w:color w:val="123456"/>', $documentXml);
        $this->assertStringContainsString('<w:shd w:val="clear" w:color="auto" w:fill="FFEEDD"/>', $documentXml);
        $this->assertStringContainsString('<w:spacing w:line="300" w:lineRule="auto"/>', $documentXml);
        $this->assertStringContainsString('<w:ind w:left="300"/>', $documentXml);
        $this->assertStringContainsString('<w:jc w:val="right"/>', $documentXml);
        $this->assertStringContainsString('<w:vertAlign w:val="superscript"/>', $documentXml);
        $this->assertStringContainsString(
            '<w:rFonts w:ascii="Courier New" w:hAnsi="Courier New" w:eastAsia="Courier New" w:cs="Courier New"/>',
            $documentXml
        );
        $this->assertStringContainsString('<w:color w:val="654321"/>', $documentXml);
        $this->assertStringContainsString('linha 1</w:t><w:br/><w:t xml:space="preserve">  linha 2', $documentXml);
        $this->assertStringContainsString('<w:spacing w:before="225" w:after="225"/>', $documentXml);
        $this->assertStringContainsString('<w:tblW w:w="5000" w:type="pct"/>', $documentXml);
        $this->assertStringContainsString('<w:tblLayout w:type="fixed"/>', $documentXml);
        $this->assertStringContainsString('<w:gridCol w:w="3538"/><w:gridCol w:w="6868"/>', $documentXml);
        $this->assertStringContainsString('<w:top w:val="single" w:sz="6" w:color="CCCCCC"/>', $documentXml);
        $this->assertStringContainsString('<w:top w:val="single" w:sz="6" w:color="9CA3AF"/>', $documentXml);
        $this->assertStringContainsString('<w:shd w:val="clear" w:color="auto" w:fill="D9D9D9"/>', $documentXml);
        $this->assertStringContainsString(
            '<w:tcMar><w:top w:w="90" w:type="dxa"/><w:left w:w="105" w:type="dxa"/><w:bottom w:w="90" w:type="dxa"/><w:right w:w="105" w:type="dxa"/></w:tcMar>',
            $documentXml
        );
        $this->assertStringContainsString('<w:sz w:val="12"/><w:szCs w:val="12"/>', $documentXml);
        $this->assertStringContainsString(
            '<w:spacing w:after="0" w:before="0" w:line="408" w:lineRule="auto"/><w:jc w:val="center"/>',
            $documentXml
        );
        $this->assertStringContainsString(
            '<w:pgMar w:top="1800" w:right="750" w:bottom="1500" w:left="750" w:header="375" w:footer="0" w:gutter="0"/>',
            $documentXml
        );
        $this->assertStringContainsString('DejaVu Sans', $stylesXml);
        $this->assertStringContainsString('<w:sz w:val="18"/><w:szCs w:val="18"/>', $stylesXml);
        $this->assertStringContainsString('<w:color w:val="1A1A1A"/>', $stylesXml);
        $this->assertStringContainsString(
            '<w:spacing w:after="180" w:before="0" w:line="408" w:lineRule="auto"/>',
            $stylesXml
        );
        $this->assertStringNotContainsString('Times New Roman', $stylesXml);
        $this->assertStringContainsString(
            '<w:tab w:val="num" w:pos="300"/></w:tabs><w:ind w:left="300" w:hanging="180"/>',
            $numberingXml
        );
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
            'body' => '<h1 style="font-family: Georgia; font-size: 30px">Título Casa Civil</h1>'
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
        $this->assertStringNotContainsString('Georgia', $documentXml);
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
