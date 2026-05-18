<?php

namespace App\Services;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\Project;

class LegalAnalysisService
{
    public function updateFileStatus(Project $project, File $file, FileStatus $status): void
    {
        $analysis = $project->legalAnalysis()->firstOrCreate([]);

        $analysis->files()->updateOrCreate(
            ['file_id' => $file->id],
            ['status' => $status],
        );
    }
}
