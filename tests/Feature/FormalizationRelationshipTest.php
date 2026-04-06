<?php

namespace Tests\Feature;

use App\Models\Formalization;
use App\Models\Project;
use App\Enums\ReportStatus;
use App\Enums\DeliberationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormalizationRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_formalization_belongs_to_project()
    {
        $project = Project::factory()->create();
        $formalization = Formalization::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $formalization->project);
        $this->assertEquals($project->id, $formalization->project->id);
    }

    public function test_project_has_one_formalization()
    {
        $project = Project::factory()->create();
        $formalization = Formalization::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Formalization::class, $project->formalization);
        $this->assertEquals($formalization->id, $project->formalization->id);
    }

    public function test_formalization_casts_enums()
    {
        $formalization = Formalization::factory()->create([
            'report_status' => ReportStatus::REGULAR_E_ADIMPLENTE,
            'deliberation' => DeliberationType::BATCH_CGE,
        ]);

        $this->assertInstanceOf(ReportStatus::class, $formalization->report_status);
        $this->assertInstanceOf(DeliberationType::class, $formalization->deliberation);
        $this->assertEquals(ReportStatus::REGULAR_E_ADIMPLENTE, $formalization->report_status);
    }
}
