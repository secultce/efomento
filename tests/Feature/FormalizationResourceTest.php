<?php

namespace Tests\Feature;

use App\Http\Resources\FormalizationResource;
use App\Models\Formalization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FormalizationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transforms_formalization_attributes(): void
    {
        $formalization = Formalization::factory()->create();

        $data = (new FormalizationResource($formalization))->toArray(Request::create('/'));

        $this->assertSame($formalization->id, $data['id']);
        $this->assertSame($formalization->project_id, $data['project_id']);
        $this->assertSame($formalization->term_number, $data['term_number']);
        $this->assertEquals($formalization->term_signed_at, $data['term_signed_at']);
        $this->assertSame($formalization->report_status?->value, $data['report_status']);
        $this->assertSame($formalization->deliberation?->value, $data['deliberation']);
        $this->assertSame($formalization->sacc_number, $data['sacc_number']);
        $this->assertSame($formalization->cge_atende_ticket?->value, $data['cge_atende_ticket']);
    }

    public function test_serializes_null_enums_without_errors(): void
    {
        $formalization = Formalization::factory()->make([
            'report_status' => null,
            'deliberation' => null,
        ]);

        $data = (new FormalizationResource($formalization))->toArray(Request::create('/'));

        $this->assertNull($data['report_status']);
        $this->assertNull($data['deliberation']);
    }

    public function test_omits_files_when_relation_is_not_loaded(): void
    {
        $formalization = Formalization::factory()->create();

        $response = (new FormalizationResource($formalization))
            ->response(Request::create('/'))
            ->getData(true);

        $this->assertArrayNotHasKey('files', $response['data']);
    }

    public function test_groups_files_by_group_when_relation_is_loaded(): void
    {
        $formalization = Formalization::factory()->create();
        $formalization->load('files');

        $response = (new FormalizationResource($formalization))
            ->response(Request::create('/'))
            ->getData(true);

        $files = $response['data']['files'];

        // A factory cria um arquivo por grupo: commitment_term, term_summary,
        // official_gazette e legal_opinion.
        $this->assertEqualsCanonicalizing(
            ['commitment_term', 'term_summary', 'official_gazette', 'legal_opinion'],
            array_keys($files)
        );
        $this->assertCount(1, $files['official_gazette']);
        $this->assertSame('Official Gazette', $files['official_gazette'][0]['name']);
    }
}
