<?php

namespace App\Repositories;

use App\Enums\FileStatus;
use App\Models\File;
use App\Models\LegalAnalysis;
use App\Models\Project;

class LegalAnalysisRepository
{
    public function updateFileStatus(Project $project, File $file, FileStatus $status): void
    {
        $analysis = $project->legalAnalysis ?? LegalAnalysis::create([
            'project_id' => $project->id,
        ]);

        $analysis->files()->updateOrCreate(
            ['file_id' => $file->id],
            ['status' => $status],
        );
    }
}
