<?php

namespace Tests\Feature;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\LegalAnalysis;
use App\Models\LegalAnalysisFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LegalAnalysisFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_analysis_file_relationships()
    {
        $analysis = LegalAnalysis::factory()->create();

        $legalAnalysisFile = LegalAnalysisFile::factory()->create([
            'legal_analysis_id' => $analysis->id,
        ]);

        $this->assertInstanceOf(LegalAnalysis::class, $legalAnalysisFile->analysis);
        $this->assertInstanceOf(File::class, $legalAnalysisFile->file);
    }

    public function test_status_is_casted_to_enum()
    {
        $analysis = LegalAnalysis::factory()->create();

        $file = LegalAnalysisFile::factory()->create([
            'legal_analysis_id' => $analysis->id,
            'status' => FileStatus::VALID,
        ]);

        $this->assertInstanceOf(FileStatus::class, $file->status);
        $this->assertEquals(FileStatus::VALID, $file->status);
    }
}
