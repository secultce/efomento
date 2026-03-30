<?php

namespace Tests\Feature;

use App\Enums\FileStatus;
use App\Models\LegalAnalysis;
use App\Models\LegalAnalysisFile;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LegalAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_analysis_relationships()
    {
        $analysis = LegalAnalysis::factory()->create();

        $this->assertInstanceOf(Project::class, $analysis->project);
        $this->assertInstanceOf(User::class, $analysis->responsibleUser);
        $this->assertInstanceOf(Collection::class, $analysis->files);
    }

    public function test_project_has_legal_analysis()
    {
        $project = Project::factory()->create();
        $analysis = LegalAnalysis::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertEquals($analysis->id, $project->legalAnalysis->id);
    }

    public function test_legal_analysis_soft_delete()
    {
        $analysis = LegalAnalysis::factory()->create();

        $analysis->delete();

        $this->assertSoftDeleted('legal_analyses', [
            'id' => $analysis->id
        ]);
    }

    public function test_cannot_create_analysis_without_project()
    {
        $this->expectException(QueryException::class);

        LegalAnalysis::create([
            'responsible_user_id' => 1
        ]);
    }

    public function test_processed_at_cast()
    {
        $analysis = LegalAnalysis::factory()->create([
            'processed_at' => now()
        ]);

        $this->assertInstanceOf(Carbon::class, $analysis->processed_at);
    }

    public function test_get_file_status_returns_correct_status()
    {
        $analysis = LegalAnalysis::factory()->create();

        $file = LegalAnalysisFile::factory()->create([
            'legal_analysis_id' => $analysis->id,
            'file_id' => 123,
            'status_id' => FileStatus::VALID,
        ]);

        $status = $analysis->getFileStatus(123);

        $this->assertEquals(FileStatus::VALID, $status);
    }

    public function test_get_file_status_returns_null_when_not_found()
    {
        $analysis = LegalAnalysis::factory()->create();

        $this->assertNull($analysis->getFileStatus(999));
    }
}
