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
        $file = LegalAnalysisFile::factory()->create();

        $this->assertInstanceOf(LegalAnalysis::class, $file->analysis);
        $this->assertInstanceOf(File::class, $file->file);
    }

    public function test_status_is_casted_to_enum()
    {
        $file = LegalAnalysisFile::factory()->create([
            'status_id' => FileStatus::VALID,
        ]);

        $this->assertInstanceOf(FileStatus::class, $file->status_id);
        $this->assertEquals(FileStatus::VALID, $file->status_id);
    }
}
